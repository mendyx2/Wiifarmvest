<div class="modal-body">
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label fw-bold">Cycle Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="name" placeholder="e.g. 2026 Season 1" required maxlength="100">
        </div>
        <div class="col-sm-6">
            <label class="form-label fw-bold">Min Contribution <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text">{{ gs('cur_sym') }}</span>
                <input type="number" class="form-control" name="min_contribution" step="0.01" min="0" required>
            </div>
        </div>
        <div class="col-sm-6">
            <label class="form-label fw-bold">Max Contribution <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text">{{ gs('cur_sym') }}</span>
                <input type="number" class="form-control" name="max_contribution" step="0.01" min="0" required>
            </div>
        </div>
        <div class="col-sm-6">
            <label class="form-label fw-bold">Min Yield % <span class="text-danger">*</span></label>
            <div class="input-group">
                <input type="number" class="form-control" name="min_yield" step="0.01" min="0" max="100" required>
                <span class="input-group-text">%</span>
            </div>
        </div>
        <div class="col-sm-6">
            <label class="form-label fw-bold">Max Yield % <span class="text-danger">*</span></label>
            <div class="input-group">
                <input type="number" class="form-control" name="max_yield" step="0.01" min="0" max="100" required>
                <span class="input-group-text">%</span>
            </div>
        </div>
        <div class="col-sm-6">
            <label class="form-label fw-bold">Actual Yield % <small class="text-muted fw-normal">(set before maturity)</small></label>
            <div class="input-group">
                <input type="number" class="form-control" name="actual_yield" step="0.01" min="0" max="100" placeholder="e.g. 18.5">
                <span class="input-group-text">%</span>
            </div>
        </div>
        <div class="col-sm-6">
            <label class="form-label fw-bold">Status <span class="text-danger">*</span></label>
            <select class="form-select" name="status" required>
                <option value="open">Open</option>
                <option value="closed">Closed</option>
            </select>
        </div>
        <div class="col-sm-6">
            <label class="form-label fw-bold">Subscription Opens At <span class="text-danger">*</span></label>
            <input type="date" class="form-control" name="subscription_opens_at" required>
        </div>
        <div class="col-sm-6">
            <label class="form-label fw-bold">Maturity Date <span class="text-danger">*</span></label>
            <input type="date" class="form-control" name="maturity_date" required>
        </div>
    </div>
</div>
