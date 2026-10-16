<!-- resources/views/components/email/button.blade.php -->
<a href="{{ $url }}" class="button" style="...">
    {{ $slot }}
</a>

<!-- In your template -->
<x-email.button url="{{ route('order.tracking') }}">
    Track Your Order
</x-email.button>