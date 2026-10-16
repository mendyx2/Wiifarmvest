{{-- One state price row on the create form. $index is a number, or __INDEX__ inside the JS template. --}}
<div class="pf-state-add-row pf-state-row">
    <div class="pf-field">
        <label class="pf-label">State</label>
        <select class="pf-input" name="state_prices[{{ $index }}][state_id]" required>
            <option value="">Select state</option>
            @foreach ($states as $state)
                <option value="{{ $state->id }}" @selected(($row['state_id'] ?? null) == $state->id)>
                    {{ $state->name }} ({{ $state->code }})
                </option>
            @endforeach
        </select>
    </div>
    <div class="pf-field">
        <label class="pf-label">Price (₦)</label>
        <div class="pf-input-prefix">
            <span class="pf-prefix">₦</span>
            <input class="pf-input" name="state_prices[{{ $index }}][price]" type="number" step="any" min="0"
                value="{{ $row['price'] ?? '' }}" placeholder="0.00" required>
        </div>
    </div>
    <div class="pf-field">
        <label class="pf-label">Mega Stockist Bonus</label>
        <div class="pf-input-suffix">
            <input class="pf-input" name="state_prices[{{ $index }}][mega_stockist_bonus]" type="number" step="0.01" min="0" max="100"
                value="{{ $row['mega_stockist_bonus'] ?? '' }}" placeholder="0">
            <span class="pf-suffix">%</span>
        </div>
    </div>
    <div class="pf-field">
        <label class="pf-label">Micro Stockist Bonus</label>
        <div class="pf-input-suffix">
            <input class="pf-input" name="state_prices[{{ $index }}][micro_stockist_bonus]" type="number" step="0.01" min="0" max="100"
                value="{{ $row['micro_stockist_bonus'] ?? '' }}" placeholder="0">
            <span class="pf-suffix">%</span>
        </div>
    </div>
    <div class="pf-state-add-actions">
        <button type="button" class="pf-state-del-btn pf-state-row-remove" title="Remove">
            <i class="las la-trash-alt"></i>
        </button>
    </div>
</div>
