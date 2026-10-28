<?php

namespace App\Traits;
 
use App\Constants\Status;
use App\Models\AdminNotification;
use App\Models\SupportAttachment;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

trait SupportTicketManager
{
    protected $files;
    protected $allowedExtension = ['jpg', 'png', 'jpeg', 'pdf', 'doc', 'docx'];
    protected $userType;
    protected $user = null;
    protected $column;
    protected $apiRequest = false;

    // The ticket list, new-ticket and ticket pages are each controller's own
    // (TicketController for members, Admin\SupportTicketController); the
    // Blade versions that were here are gone (notes Step 119).

    public function storeSupportTicket(Request $request)
    {
        $user = $this->user;

        if (!$user) {
            return to_route('home');
        }

        $ticket  = new SupportTicket();
        $message = new SupportMessage();

        $validationRule = $this->validation($request);
        if ($this->apiRequest) {
            $validator = Validator::make($request->all(), $validationRule);
            if ($validator->fails()) {
                return response()->json([
                    'remark'=>'validation_error',
                    'status'=>'error',
                    'message'=>['error'=>$validator->errors()->all()],
                ]);
            }
        }else{
            $request->validate($validationRule);
        }

        $column             = $this->column;
        $user               = $this->user;
        $ticket->$column    = $user->id;
        // rand() alone could repeat a number, and the member's link opens a ticket by its number
        do {
            $number = random_int(100000, 999999);
        } while (SupportTicket::where('ticket', $number)->exists());
        $ticket->ticket     = $number;
        $ticket->name       = $user->fullname;
        $ticket->email      = $user->email;
        $ticket->subject    = $request->subject;
        $ticket->last_reply = Carbon::now();
        $ticket->status     = Status::TICKET_OPEN;
        $ticket->priority   = $request->priority;
        $ticket->save();


        $message->support_ticket_id   = $ticket->id;
        $message->message             = $request->message;
        $message->save();

        $adminNotification            = new AdminNotification();
        $adminNotification->$column   = $user->id;
        $adminNotification->title     = 'New support ticket has opened';
        $adminNotification->click_url = urlPath('admin.ticket.view', $ticket->id);
        $adminNotification->save();

        if ($request->hasFile('attachments')) {
            $uploadAttachments = $this->storeSupportAttachments($message->id);
            if ($uploadAttachments != 200){
                if($this->apiRequest){
                    $notify[] = 'File could not upload';
                    return response()->json([
                        'remark'=>'file_upload_error',
                        'status'=>'error',
                        'message'=>['error'=>$notify],
                    ]);
                }
                return back()->withNotify($uploadAttachments);
            }
        }

        if($this->apiRequest){
            $notify[] = 'Ticket opened successfully';
            return response()->json([
                'remark'=>'ticket_open',
                'status'=>'success',
                'message'=>['success'=>$notify],
                'data'=>[
                    'ticket'=>$ticket
                ]
            ]);
        }

        $notify[] = ['success', 'Ticket opened successfully!'];


        return to_route($this->redirectLink, $ticket->ticket)->withNotify($notify);
    }

    public function replyTicket(Request $request, $id)
    {
        $user = $this->user;
        $userId = 0;
        if ($user) {
            $userId = $user->id;
        }
        $ticket = SupportTicket::where('id', $id)->first();
        if (!$ticket) {
            if ($this->apiRequest) {
                $notify[] = 'Ticket not found';
                return response()->json([
                    'remark'=>'ticket_not_found',
                    'status'=>'error',
                    'message'=>['error'=>$notify],
                ]);
            }
            abort(404);
        }
        if (($this->userType == 'user') && ($userId != $ticket->user_id)) {
            if ($this->apiRequest) {
                $notify[] = 'Unauthorized user';
                return response()->json([
                    'remark'=>'unauthorized',
                    'status'=>'error',
                    'message'=>['error'=>$notify],
                ]);
            }
            abort(404);
        }
        $message = new SupportMessage();

        $request->merge(['ticket_reply' => 1]);

        $validationRule = $this->validation($request);
        if ($this->apiRequest) {
            $validator = Validator::make($request->all(), $validationRule);
            if ($validator->fails()) {
                return response()->json([
                    'remark'=>'validation_error',
                    'status'=>'error',
                    'message'=>['error'=>$validator->errors()->all()],
                ]);
            }
        }else{
            $request->validate($validationRule);
        }

        $ticket->status = $this->userType != 'admin' ? Status::TICKET_REPLY : Status::TICKET_ANSWER;
        $ticket->last_reply = Carbon::now();
        $ticket->save();
        $message->support_ticket_id = $ticket->id;
        if ($this->userType == 'admin') {
            $message->admin_id = $user->id;
        }

        $message->message = $request->message;
        $message->save();

        if ($request->hasFile('attachments')) {
            $uploadAttachments = $this->storeSupportAttachments($message->id);
            if ($uploadAttachments != 200){
                if($this->apiRequest){
                    $notify[] = 'File could not upload';
                    return response()->json([
                        'remark'=>'file_upload_error',
                        'status'=>'error',
                        'message'=>['error'=>$notify],
                    ]);
                }
                return back()->withNotify($uploadAttachments);
            }
        }

        if ($this->userType == 'admin') {
            $createLog = false;
            $user = $ticket;
            $sendVia = ['email','sms'];
            if ($ticket->user_id != 0) {
                $createLog = true;
                $user = $ticket->user;
                $sendVia = null;
            }

            notify($user, 'ADMIN_SUPPORT_REPLY', [
                'ticket_id' => $ticket->ticket,
                'ticket_subject' => $ticket->subject,
                'reply' => $request->message,
                'link' => route('ticket.view', $ticket->ticket),
            ], $sendVia, $createLog);
        }

        if($this->apiRequest){
            $notify[] = 'Ticket replied successfully';
            return response()->json([
                'remark'=>'ticket_replied',
                'status'=>'success',
                'message'=>['success'=>$notify],
                'data'=>[
                    'ticket'=>$ticket,
                    'message'=>$message
                ]
            ]);
        }

        $notify[] = ['success', 'Support ticket replied successfully!'];

        return back()->withNotify($notify);
    }

    protected function storeSupportAttachments($messageId)
    {
        $path = getFilePath('ticket');

        foreach ($this->files as  $file) {
            try {
                $attachment = new SupportAttachment();
                $attachment->support_message_id = $messageId;
                $attachment->attachment = fileUploader($file, $path);
                $attachment->save();
            } catch (\Exception $exp) {
                $notify[] = ['error', 'File could not upload'];
                return $notify;
            }
        }

        return 200;
    }

    /** The largest attachment, as shown on the forms: 10 MB, or less when PHP allows less. */
    protected function attachmentLimit(): string
    {
        $php = ini_get('upload_max_filesize');
        return $php && ini_parse_quantity($php) < 10 * 1024 * 1024 ? convertToReadableSize($php) : '10MB';
    }

    protected function validation($request)
    {
        $this->files = (array) $request->file('attachments');

        // There was no size limit; at most 5 files of 10 MB each
        return [
            'attachments'   => ['nullable', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'max:10240',
                function ($attribute, $file, $fail) {
                    if (!in_array(strtolower($file->getClientOriginalExtension()), $this->allowedExtension, true)) {
                        $fail('Only png, jpg, jpeg, pdf, doc, docx files are allowed');
                    }
                },
            ],
            'subject'   => 'required_without:ticket_reply|max:255',
            'priority'  => 'required_without:ticket_reply|in:1,2,3',
            'message'   => 'required',
        ];
    }

    private function convertToMb($value) {
        $unit = strtolower(substr($value, -1));
        $value = substr($value, 0, -1);
        if($unit == 'k'){
            return $value / 1024;
        }
        if($unit == 'm'){
            return $value;
        }
        if($unit == 'g'){
            return $value * 1024;
        }
        return $value;
      }

    public function closeTicket($id)
    {
        $user = $this->user;
        $ticket = SupportTicket::where('id', $id)->first();
        if (!$ticket) {
            if ($this->apiRequest) {
                $notify[] = 'Ticket not found';
                return response()->json([
                    'remark'=>'ticket_not_found',
                    'status'=>'error',
                    'message'=>['error'=>$notify],
                ]);
            }
            abort(404);
        }
        if ($this->userType != 'admin') {
            $column = $this->column;
            if ($user->id != $ticket->$column) {
                if ($this->apiRequest) {
                    $notify[] = 'Unauthorized user';
                    return response()->json([
                        'remark'=>'unauthorized',
                        'status'=>'error',
                        'message'=>['error'=>$notify],
                    ]);
                }
                abort(403);
            }
        }

        $ticket->status = Status::TICKET_CLOSE;
        $ticket->save();

        if($this->apiRequest){
            $notify[] = 'Ticket closed successfully';
            return response()->json([
                'remark'=>'ticket_closed',
                'status'=>'success',
                'message'=>['success'=>$notify]
            ]);
        }

        $notify[] = ['success', 'Support ticket closed successfully!'];
        return back()->withNotify($notify);
    }

    public function ticketDownload($attachmentId)
    {
        $attachment = SupportAttachment::find(decrypt($attachmentId));
        if (!$attachment) {
            if ($this->apiRequest) {
                $notify[] = 'Attachment not found';
                return response()->json([
                    'remark'=>'attachment_not_found',
                    'status'=>'error',
                    'message'=>['error'=>$notify],
                ]);
            }
            abort(404);
        }
        $file     = $attachment->attachment;
        $fullPath = getFilePath('ticket') . '/' . $file;
        // A missing file used to end in a PHP warning and a broken download
        abort_unless(is_file($fullPath), 404);
        $title = slug($attachment->supportMessage->ticket->subject) ?: 'attachment';

        return response()->download($fullPath, $title . '.' . pathinfo($file, PATHINFO_EXTENSION));
    }
}
