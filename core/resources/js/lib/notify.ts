import type { NotifyMessage } from '@/types';

/** Show toasts with the same popup the Blade pages use. */
export function showNotifications(messages: NotifyMessage[] | undefined): void {
    if (!messages?.length || !window.notify) {
        return;
    }

    for (const [status, message] of messages) {
        window.notify(status, message);
    }
}
