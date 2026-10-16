@extends($activeTemplate . 'layouts.master')
@section('content')

<div class="notif-page">

    <div class="notif-header">
        <h5 class="notif-title"><i class="las la-bell"></i> Notifications</h5>
        <span class="notif-count">{{ $notifications->total() }} total</span>
    </div>

    <div class="notif-list-card">
        @forelse($notifications as $notif)
        @php
            $typeIcon = match($notif->notification_type) {
                'email' => 'la-envelope',
                'sms'   => 'la-sms',
                'push'  => 'la-bell',
                default => 'la-bell',
            };
            $typeLabel = match($notif->notification_type) {
                'email' => 'Email',
                'sms'   => 'SMS',
                'push'  => 'Push',
                default => ucfirst($notif->notification_type),
            };
        @endphp
        <div class="notif-item {{ !$notif->user_read ? 'notif-unread' : '' }}">
            @if(!$notif->user_read)
                <span class="notif-unread-dot"></span>
            @endif
            <div class="notif-icon-wrap {{ $notif->is_admin_notice ? 'notif-icon-admin' : 'notif-icon-' . $notif->notification_type }}">
                <i class="las {{ $notif->is_admin_notice ? 'la-user-shield' : $typeIcon }}"></i>
            </div>
            <div class="notif-body">
                <div class="notif-subject">{{ $notif->subject ?: 'Notification' }}</div>
                <div class="notif-message">{{ strip_tags($notif->message) }}</div>
                <div class="notif-meta">
                    @if($notif->is_admin_notice)
                        <span class="notif-admin-badge"><i class="las la-user-shield"></i> Personal Notice</span>
                        @if($notif->notifyingAdmin)
                            <span class="notif-from">From {{ $notif->notifyingAdmin->name ?? $notif->notifyingAdmin->username }}</span>
                        @endif
                    @else
                        <span class="notif-type-badge">{{ $typeLabel }}</span>
                    @endif
                    <span class="notif-time">{{ $notif->created_at->format('M d, Y · h:i A') }}</span>
                </div>
            </div>
        </div>
        @empty
        <div class="notif-empty">
            <i class="las la-bell-slash notif-empty-icon"></i>
            <h6>No Notifications Yet</h6>
            <p>You'll see system alerts and updates here.</p>
        </div>
        @endforelse
    </div>

    @if($notifications->hasPages())
    <div class="notif-pagination">{{ paginateLinks($notifications) }}</div>
    @endif

</div>

@endsection
