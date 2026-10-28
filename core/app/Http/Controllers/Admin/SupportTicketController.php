<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Traits\SupportTicketManager;
use Inertia\Inertia;

/**
 * Support Tickets (Pages/Admin/Support/*): the members' tickets, one list with
 * a tab per status, and one ticket's conversation with the reply form.
 * Replying, closing and downloads come from SupportTicketManager, shared with
 * the member side.
 */
class SupportTicketController extends Controller
{
    use SupportTicketManager;

    public function __construct()
    {
        parent::__construct();
        $this->userType = 'admin';
        $this->column   = 'admin_id';
        $this->user     = auth()->guard('admin')->user();
    }

    private const LISTS = [
        'all'      => ['Support Tickets', 'admin.ticket.index'],
        'pending'  => ['Pending Tickets', 'admin.ticket.pending'],
        'answered' => ['Answered Tickets', 'admin.ticket.answered'],
        'closed'   => ['Closed Tickets', 'admin.ticket.closed'],
    ];

    public function tickets()
    {
        return $this->list('all');
    }

    public function pendingTicket()
    {
        return $this->list('pending');
    }

    public function closedTicket()
    {
        return $this->list('closed');
    }

    public function answeredTicket()
    {
        return $this->list('answered');
    }

    private function list(string $tab)
    {
        $pageTitle = self::LISTS[$tab][0];
        $query     = SupportTicket::searchable(['name', 'subject', 'ticket'])->with('user')->orderBy('id', 'desc');
        if ($tab !== 'all') {
            $query->$tab();
        }

        return Inertia::render('Admin/Support/Tickets', [
            'title'   => $pageTitle,
            'tab'     => $tab,
            'tabs'    => collect(self::LISTS)->map(fn ($l, $key) => ['key' => $key, 'label' => $l[0], 'url' => route($l[1])])->values(),
            'search'  => (string) request('search'),
            'tickets' => $query->paginate(getPaginate())->withQueryString()->through(fn (SupportTicket $t) => [
                'id'        => $t->id,
                'number'    => (string) $t->ticket,
                'subject'   => (string) $t->subject,
                'name'      => (string) ($t->user?->fullname ?: $t->name),
                'username'  => (string) $t->user?->username,
                'userUrl'   => $t->user_id ? route('admin.users.detail', $t->user_id) : null,
                'status'    => self::status($t->status),
                'priority'  => self::priority($t->priority),
                'lastReply' => $t->last_reply ? diffForHumans($t->last_reply) : '—',
                'url'       => route('admin.ticket.view', $t->id),
            ]),
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    /** [label, badge tone] */
    private static function status($status): array
    {
        return match ((int) $status) {
            Status::TICKET_OPEN   => ['Open', 'success'],
            Status::TICKET_ANSWER => ['Answered', 'primary'],
            Status::TICKET_REPLY  => ['Customer Reply', 'warning'],
            default               => ['Closed', 'dark'],
        };
    }

    private static function priority($priority): array
    {
        return match ((int) $priority) {
            Status::PRIORITY_HIGH   => ['High', 'danger'],
            Status::PRIORITY_MEDIUM => ['Medium', 'warning'],
            default                 => ['Low', 'dark'],
        };
    }

    public function ticketReply($id)
    {
        $ticket   = SupportTicket::with('user')->findOrFail($id);
        $messages = SupportMessage::with('admin', 'attachments')->where('support_ticket_id', $ticket->id)->orderBy('id', 'desc')->get();

        return Inertia::render('Admin/Support/Ticket', [
            'ticket' => [
                'id'       => $ticket->id,
                'number'   => (string) $ticket->ticket,
                'subject'  => (string) $ticket->subject,
                'name'     => (string) ($ticket->user?->fullname ?: $ticket->name),
                'username' => (string) $ticket->user?->username,
                'email'    => (string) $ticket->email,
                'userUrl'  => $ticket->user_id ? route('admin.users.detail', $ticket->user_id) : null,
                'status'   => self::status($ticket->status),
                'priority' => self::priority($ticket->priority),
                'closed'   => (int) $ticket->status === Status::TICKET_CLOSE,
                'opened'   => showDateTime($ticket->created_at),
            ],
            'messages' => $messages->map(fn (SupportMessage $m) => [
                'id'          => $m->id,
                'staff'       => (int) $m->admin_id > 0,
                'author'      => (int) $m->admin_id > 0 ? (string) ($m->admin?->name ?: 'Staff') : (string) ($ticket->user?->fullname ?: $ticket->name),
                'message'     => (string) $m->message,
                'date'        => showDateTime($m->created_at, 'l, dS F Y @ h:i a'),
                'attachments' => $m->attachments->values()->map(fn ($a, $i) => ['name' => 'Attachment ' . ($i + 1), 'url' => route('admin.ticket.download', encrypt($a->id))])->all(),
            ]),
            'maxUpload'  => $this->attachmentLimit(),
            'urls'       => [
                'reply'  => route('admin.ticket.reply', $ticket->id),
                'close'  => route('admin.ticket.close', $ticket->id),
                'delete' => route('admin.ticket.delete', ':id'),
                'back'   => route('admin.ticket.index'),
            ],
        ])->withViewData(['pageTitle' => 'Reply Ticket']);
    }

    /** Delete one message of a ticket, with its attachments. */
    public function ticketDelete($id)
    {
        $message = SupportMessage::with('attachments')->findOrFail($id);
        $path    = getFilePath('ticket');
        foreach ($message->attachments as $attachment) {
            fileManager()->removeFile($path . '/' . $attachment->attachment);
            $attachment->delete();
        }
        $message->delete();

        $notify[] = ['success', 'Message deleted successfully'];
        return back()->withNotify($notify);
    }
}
