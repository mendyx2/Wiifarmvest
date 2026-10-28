<?php

namespace App\Http\Controllers;

use App\Constants\Status;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Traits\SupportTicketManager;
use Inertia\Inertia;

class TicketController extends Controller
{
    use SupportTicketManager;

    public function __construct()
    {
        parent::__construct();
        $this->layout = 'frontend';
        $this->redirectLink = 'ticket.view';
        $this->userType     = 'user';
        $this->column       = 'user_id';
        $this->user = auth()->user();
        if ($this->user) {
            $this->layout = 'master';
        }
    }

    /*
     * The three pages below are React (Inertia) versions of the trait's
     * supportTicket(), openSupportTicket() and viewTicket(). The trait is also
     * used by the admin panel, which keeps its Blade pages, so it is not
     * changed. Saving, replying, closing and downloads still use the trait.
     */

    public function supportTicket()
    {
        $pageTitle = 'Support Tickets';
        $supports  = SupportTicket::where('user_id', $this->user->id)->orderBy('id', 'desc')->paginate(getPaginate());

        // Was the Blade view Template::user.support.index (deleted)
        return Inertia::render('User/Tickets', [
            'tickets' => $supports->through(fn ($ticket) => [
                'id'        => $ticket->id,
                'number'    => $ticket->ticket,
                'subject'   => __($ticket->subject),
                'status'    => self::status($ticket->status),
                'priority'  => self::priority($ticket->priority),
                'lastReply' => diffForHumans($ticket->last_reply),
                'url'       => route('ticket.view', $ticket->ticket),
            ]),
            'openUrl' => route('ticket.open'),
        ])->withViewData(compact('pageTitle'));
    }

    public function openSupportTicket()
    {
        $pageTitle = 'Open Ticket';

        // Was the Blade view Template::user.support.create (deleted)
        return Inertia::render('User/TicketCreate', [
            'maxUpload' => $this->attachmentLimit(),
            'storeUrl'  => route('ticket.store'),
            'listUrl'   => route('ticket.index'),
        ])->withViewData(compact('pageTitle'));
    }

    public function viewTicket($ticket)
    {
        $pageTitle = 'View Ticket';

        // Only the logged-in user's own tickets. (The trait also lets anyone
        // with the number open a guest ticket, user_id = 0; these routes
        // require login, so that case does not apply here.)
        $myTicket = SupportTicket::where('ticket', $ticket)->where('user_id', $this->user->id)->orderBy('id', 'desc')->firstOrFail();
        $messages = SupportMessage::where('support_ticket_id', $myTicket->id)->with('admin', 'attachments')->orderBy('id', 'desc')->get();

        // Was the Blade view Template::user.support.view (deleted)
        return Inertia::render('User/TicketView', [
            'ticket' => [
                'id'      => $myTicket->id,
                'number'  => $myTicket->ticket,
                'subject' => $myTicket->subject,
                'status'  => self::status($myTicket->status),
                'closed'  => $myTicket->status == Status::TICKET_CLOSE,
            ],
            'messages' => $messages->map(fn ($message) => [
                'id'          => $message->id,
                'fromStaff'   => $message->admin_id != 0,
                'author'      => $message->admin_id != 0 ? (string) @$message->admin->name : $myTicket->name,
                'postedOn'    => showDateTime($message->created_at, 'l, dS F Y @ h:i a'),
                'message'     => $message->message,
                'attachments' => $message->attachments->map(fn ($file) => route('ticket.download', encrypt($file->id)))->values(),
            ]),
            'maxUpload' => $this->attachmentLimit(),
            'replyUrl'  => route('ticket.reply', $myTicket->id),
            'closeUrl'  => route('ticket.close', $myTicket->id),
            'listUrl'   => route('ticket.index'),
            'emptyImage' => asset('assets/images/empty_list.png'),
        ])->withViewData(compact('pageTitle'));
    }

    /** Mirrors SupportTicket::statusBadge, as data. */
    private static function status($status): array
    {
        return match ((int) $status) {
            Status::TICKET_OPEN   => ['label' => 'Open', 'tone' => 'success'],
            Status::TICKET_ANSWER => ['label' => 'Answered', 'tone' => 'primary'],
            Status::TICKET_REPLY  => ['label' => 'Customer Reply', 'tone' => 'warning'],
            Status::TICKET_CLOSE  => ['label' => 'Closed', 'tone' => 'dark'],
            default               => ['label' => '', 'tone' => 'dark'],
        };
    }

    private static function priority($priority): ?array
    {
        return match ((int) $priority) {
            Status::PRIORITY_LOW    => ['label' => 'Low', 'tone' => 'dark'],
            Status::PRIORITY_MEDIUM => ['label' => 'Medium', 'tone' => 'warning'],
            Status::PRIORITY_HIGH   => ['label' => 'High', 'tone' => 'danger'],
            default                 => null,
        };
    }
}
