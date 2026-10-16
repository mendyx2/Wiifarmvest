@extends('admin.layouts.app')
@section('panel')

<div class="row gy-4">

    {{-- Upload form --}}
    <div class="col-lg-4">
        <div class="card b-radius--10">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="las la-photo-video me-1"></i> Upload Flashcard</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.flashcard.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="form-group mb-3">
                        <label class="form-label">Title <small class="text-muted">(optional)</small></label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Welcome Video" value="{{ old('title') }}">
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Description <small class="text-muted">(optional)</small></label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Short description…">{{ old('description') }}</textarea>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" id="typeSelect" required>
                            <option value="">— select type —</option>
                            <option value="image" @selected(old('type') == 'image')>Image</option>
                            <option value="video" @selected(old('type') == 'video')>Video</option>
                        </select>
                    </div>

                    <div class="form-group mb-4">
                        <label class="form-label">File <span class="text-danger">*</span></label>
                        <input type="file" name="file" class="form-control" id="fileInput" required
                               accept="image/*,video/*">
                        <small class="text-muted" id="fileHint">Max 50 MB</small>
                        <div class="mt-2" id="previewWrap" style="display:none">
                            <img id="imgPreview" src="" class="img-thumbnail" style="max-height:160px;display:none">
                            <video id="vidPreview" controls style="max-height:160px;width:100%;display:none"></video>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="las la-upload me-1"></i> Upload Flashcard
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- List --}}
    <div class="col-lg-8">
        <div class="card b-radius--10">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">All Flashcards ({{ $flashcards->total() }})</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table--light style--two mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Preview</th>
                                <th>Title / Description</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Uploaded</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($flashcards as $fc)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    @if($fc->type === 'image')
                                        <img src="{{ $fc->file_url }}" alt="preview"
                                             style="width:64px;height:48px;object-fit:cover;border-radius:6px;">
                                    @else
                                        <div style="width:64px;height:48px;background:#1E2D3D;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#16A34A;font-size:1.4rem;">
                                            <i class="las la-film"></i>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $fc->title ?: '—' }}</strong>
                                    @if($fc->description)
                                        <br><small class="text-muted">{{ Str::limit($fc->description, 60) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge--{{ $fc->type === 'image' ? 'info' : 'primary' }}">
                                        {{ ucfirst($fc->type) }}
                                    </span>
                                </td>
                                <td>
                                    @if($fc->is_active)
                                        <span class="badge badge--success">Active</span>
                                    @else
                                        <span class="badge badge--danger">Inactive</span>
                                    @endif
                                </td>
                                <td>{{ $fc->created_at->format('M d, Y') }}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <form action="{{ route('admin.flashcard.toggle', $fc->id) }}" method="POST">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn--{{ $fc->is_active ? 'warning' : 'success' }}"
                                                    title="{{ $fc->is_active ? 'Deactivate' : 'Activate' }}">
                                                <i class="las la-{{ $fc->is_active ? 'eye-slash' : 'eye' }}"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.flashcard.destroy', $fc->id) }}" method="POST"
                                              onsubmit="return confirm('Delete this flashcard? This cannot be undone.')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn--danger" title="Delete">
                                                <i class="las la-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No flashcards uploaded yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($flashcards->hasPages())
            <div class="card-footer">
                {{ paginateLinks($flashcards) }}
            </div>
            @endif
        </div>
    </div>

</div>

@endsection

@push('script')
<script>
(function() {
    var typeSelect = document.getElementById('typeSelect');
    var fileInput  = document.getElementById('fileInput');
    var hint       = document.getElementById('fileHint');
    var previewWrap = document.getElementById('previewWrap');
    var imgPreview  = document.getElementById('imgPreview');
    var vidPreview  = document.getElementById('vidPreview');

    typeSelect.addEventListener('change', function() {
        if (this.value === 'image') {
            fileInput.accept = 'image/*';
            hint.textContent = 'JPG, PNG, GIF, WebP — max 50 MB';
        } else if (this.value === 'video') {
            fileInput.accept = 'video/*';
            hint.textContent = 'MP4, MOV, WebM — max 50 MB';
        } else {
            fileInput.accept = 'image/*,video/*';
            hint.textContent = 'Max 50 MB';
        }
        resetPreview();
    });

    fileInput.addEventListener('change', function() {
        var file = this.files[0];
        if (!file) { resetPreview(); return; }
        var url = URL.createObjectURL(file);
        previewWrap.style.display = 'block';
        if (file.type.startsWith('image/')) {
            imgPreview.src = url;
            imgPreview.style.display = 'block';
            vidPreview.style.display = 'none';
        } else {
            vidPreview.src = url;
            vidPreview.style.display = 'block';
            imgPreview.style.display = 'none';
        }
    });

    function resetPreview() {
        previewWrap.style.display = 'none';
        imgPreview.src = '';
        vidPreview.src = '';
        imgPreview.style.display = 'none';
        vidPreview.style.display = 'none';
    }
})();
</script>
@endpush
