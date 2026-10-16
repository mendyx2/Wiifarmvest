<div class="modal-body">
    <div class="row g-3">
        <div class="col-sm-8">
            <label class="form-label fw-bold">Product Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="name" placeholder="e.g. Standard Cooperative Loan" required maxlength="100">
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-bold">Interest Rate (% p.a.) <span class="text-danger">*</span></label>
            <div class="input-group">
                <input type="number" class="form-control" name="interest_rate" step="0.01" min="0" max="100" required>
                <span class="input-group-text">%</span>
            </div>
        </div>
        <div class="col-12">
            <label class="form-label fw-bold">Description</label>
            <input type="text" class="form-control" name="description" maxlength="255" placeholder="Brief description shown to members">
        </div>
        <div class="col-sm-6">
            <label class="form-label fw-bold">Minimum Loan Amount <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text">{{ gs('cur_sym') }}</span>
                <input type="number" class="form-control" name="min_loan_amount" step="0.01" min="0" required>
            </div>
        </div>
        <div class="col-sm-6">
            <label class="form-label fw-bold">Maximum Loan Amount <small class="text-muted">(blank = unlimited)</small></label>
            <div class="input-group">
                <span class="input-group-text">{{ gs('cur_sym') }}</span>
                <input type="number" class="form-control" name="max_loan_amount" step="0.01" min="0" placeholder="Leave blank for no cap">
            </div>
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-bold">Savings Multiple <span class="text-danger">*</span>
                <small class="text-muted fw-normal">(max loan = savings × multiple)</small>
            </label>
            <div class="input-group">
                <input type="number" class="form-control" name="savings_multiple" step="0.1" min="1" required>
                <span class="input-group-text">×</span>
            </div>
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-bold">Min Savings Wallet Balance <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text">{{ gs('cur_sym') }}</span>
                <input type="number" class="form-control" name="min_savings_threshold" step="0.01" min="0" required>
            </div>
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-bold">Min Membership Days <span class="text-danger">*</span></label>
            <div class="input-group">
                <input type="number" class="form-control" name="min_membership_days" min="0" required>
                <span class="input-group-text">days</span>
            </div>
        </div>
        <div class="col-12">
            <label class="form-label fw-bold">Tenure Options <span class="text-danger">*</span>
                <small class="text-muted fw-normal">
                    Comma-separated. Use <strong>w</strong> for weeks, <strong>m</strong> for months.
                    e.g. <code>2w,4w,1m,3m,6m,12m</code>
                </small>
            </label>
            <input type="text" class="form-control" name="tenure_options"
                   placeholder="2w,4w,1m,3m,6m,12m" required>
        </div>
        <div class="col-sm-6">
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" name="auto_approve" id="auto_approve_chk" value="1">
                <label class="form-check-label fw-bold" for="auto_approve_chk">
                    Auto-Approve &amp; Disburse
                    <small class="text-muted fw-normal d-block">Skip admin review; funds credited immediately.</small>
                </label>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" name="is_active" id="is_active_chk" value="1" checked>
                <label class="form-check-label fw-bold" for="is_active_chk">
                    Active
                    <small class="text-muted fw-normal d-block">Members can apply for this product.</small>
                </label>
            </div>
        </div>
    </div>
</div>
