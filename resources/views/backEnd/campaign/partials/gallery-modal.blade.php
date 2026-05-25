<div class="campaign-gallery-modal" id="campaignGalleryModal" aria-hidden="true">
    <div class="campaign-gallery-dialog">
        <div class="campaign-gallery-card">
            <div class="campaign-gallery-header">
                <h5 class="mb-0">Campaign Image Gallery</h5>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="campaignGallerySelectAll">Select All</button>
                    <button type="button" class="btn btn-sm btn-danger" id="campaignGalleryDeleteSelected">Delete Selected</button>
                    <button type="button" class="btn btn-sm btn-light campaign-gallery-close">Close</button>
                </div>
            </div>
            <div class="campaign-gallery-message d-none" id="campaignGalleryMessage"></div>
            <div class="campaign-gallery-body">
                @forelse($campaignGalleryImages as $galleryImage)
                    <div class="campaign-gallery-item"
                         data-path="{{ $galleryImage['path'] }}"
                         data-url="{{ $galleryImage['url'] }}"
                         title="{{ $galleryImage['name'] }}">
                        <label class="campaign-gallery-check">
                            <input type="checkbox" class="campaign-gallery-select" value="{{ $galleryImage['path'] }}">
                        </label>
                        <button type="button" class="campaign-gallery-pick">
                            <img src="{{ $galleryImage['url'] }}" alt="">
                            <span>{{ $galleryImage['name'] }}</span>
                        </button>
                    </div>
                @empty
                    <div class="text-center text-muted py-4">No campaign images found.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<style>
    .campaign-gallery-modal {
        position: fixed;
        inset: 0;
        z-index: 1060;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 24px;
        background: rgba(15, 23, 42, .5);
    }

    .campaign-gallery-modal.show {
        display: flex;
    }

    .campaign-gallery-dialog {
        width: min(980px, 100%);
        max-height: calc(100vh - 48px);
    }

    .campaign-gallery-card {
        overflow: hidden;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 20px 60px rgba(15, 23, 42, .25);
    }

    .campaign-gallery-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 16px;
        border-bottom: 1px solid #e6edf5;
    }

    .campaign-gallery-body {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 12px;
        max-height: calc(100vh - 150px);
        overflow-y: auto;
        padding: 16px;
    }

    .campaign-gallery-message {
        margin: 12px 16px 0;
        padding: 8px 10px;
        border-radius: 6px;
        background: #ecfdf3;
        color: #166534;
        font-size: 13px;
    }

    .campaign-gallery-item {
        position: relative;
        padding: 8px;
        text-align: left;
        background: #fff;
        border: 1px solid #dce5ef;
        border-radius: 6px;
    }

    .campaign-gallery-item:hover {
        border-color: #3b82f6;
        box-shadow: 0 8px 18px rgba(59, 130, 246, .12);
    }

    .campaign-gallery-check {
        position: absolute;
        top: 8px;
        left: 8px;
        z-index: 2;
        display: grid;
        width: 26px;
        height: 26px;
        place-items: center;
        background: rgba(255, 255, 255, .9);
        border-radius: 4px;
    }

    .campaign-gallery-pick {
        width: 100%;
        padding: 0;
        text-align: left;
        background: transparent;
        border: 0;
    }

    .campaign-gallery-item img {
        width: 100%;
        aspect-ratio: 1 / 1;
        object-fit: cover;
        border-radius: 4px;
        background: #f3f6f9;
    }

    .campaign-gallery-item span {
        display: block;
        margin-top: 6px;
        overflow: hidden;
        color: #536171;
        font-size: 12px;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .campaign-existing-review-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 8px;
    }

    .campaign-existing-review-item {
        position: relative;
        width: 86px;
        border: 1px solid #dce5ef;
        border-radius: 6px;
        overflow: hidden;
        background: #fff;
    }

    .campaign-existing-review-item img {
        width: 100%;
        height: 72px;
        object-fit: cover;
    }

    .campaign-existing-review-item button {
        width: 100%;
        border: 0;
        background: #ef4444;
        color: #fff;
        font-size: 11px;
        line-height: 22px;
    }
</style>

<script>
    (function () {
        const modal = document.getElementById('campaignGalleryModal');
        let activeMode = 'single';
        let activeInput = null;
        let activePreview = null;
        let activeList = null;
        const deleteEndpoint = @json(route('admin.campaign.gallery.delete'));
        const csrfToken = @json(csrf_token());
        const message = document.getElementById('campaignGalleryMessage');

        function openGallery(button) {
            activeMode = button.dataset.mode || 'single';
            activeInput = document.getElementById(button.dataset.targetInput || '');
            activePreview = document.getElementById(button.dataset.preview || '');
            activeList = document.getElementById(button.dataset.list || '');
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closeGallery() {
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        function setSingleImage(path, url) {
            if (activeInput) {
                activeInput.value = path;
            }
            if (activePreview) {
                activePreview.innerHTML = `<img src="${url}" alt="" class="edit-image border"><div class="small text-muted mt-1">${path}</div>`;
            }
        }

        function addReviewImage(path, url) {
            const exists = activeList && Array.from(activeList.children).some((item) => item.dataset.path === path);
            if (!activeList || exists) {
                return;
            }

            const item = document.createElement('div');
            item.className = 'campaign-existing-review-item';
            item.dataset.path = path;
            item.innerHTML = `
                <img src="${url}" alt="">
                <input type="hidden" name="review_image_existing[]" value="${path}">
                <button type="button">Remove</button>
            `;
            activeList.appendChild(item);
        }

        function showGalleryMessage(text, type = 'success') {
            message.textContent = text;
            message.className = `campaign-gallery-message ${type === 'error' ? 'bg-danger text-white' : ''}`;
        }

        document.querySelectorAll('.campaign-gallery-open').forEach((button) => {
            button.addEventListener('click', () => openGallery(button));
        });

        document.querySelectorAll('.campaign-gallery-clear').forEach((button) => {
            button.addEventListener('click', () => {
                const input = document.getElementById(button.dataset.targetInput || '');
                const preview = document.getElementById(button.dataset.preview || '');
                if (input) input.value = '';
                if (preview) preview.innerHTML = '';
            });
        });

        document.querySelectorAll('.campaign-gallery-close').forEach((button) => {
            button.addEventListener('click', closeGallery);
        });

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeGallery();
            }
        });

        document.querySelectorAll('.campaign-gallery-pick').forEach((button) => {
            button.addEventListener('click', () => {
                const item = button.closest('.campaign-gallery-item');
                if (activeMode === 'multiple') {
                    addReviewImage(item.dataset.path, item.dataset.url);
                } else {
                    setSingleImage(item.dataset.path, item.dataset.url);
                }
                closeGallery();
            });
        });

        document.getElementById('campaignGallerySelectAll').addEventListener('click', function () {
            const checkboxes = Array.from(document.querySelectorAll('.campaign-gallery-select'));
            const shouldCheck = checkboxes.some((checkbox) => !checkbox.checked);
            checkboxes.forEach((checkbox) => {
                checkbox.checked = shouldCheck;
            });
            this.textContent = shouldCheck ? 'Clear Selection' : 'Select All';
        });

        document.getElementById('campaignGalleryDeleteSelected').addEventListener('click', async function () {
            const checked = Array.from(document.querySelectorAll('.campaign-gallery-select:checked'));
            const images = checked.map((checkbox) => checkbox.value);

            if (!images.length) {
                showGalleryMessage('Please select image first.', 'error');
                return;
            }

            if (!confirm(`Delete ${images.length} selected image(s)? This cannot be undone.`)) {
                return;
            }

            this.disabled = true;

            try {
                const response = await fetch(deleteEndpoint, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ images }),
                });
                const payload = await response.json();

                if (!response.ok || !payload.success) {
                    throw new Error(payload.message || 'Failed to delete selected images.');
                }

                checked.forEach((checkbox) => {
                    checkbox.closest('.campaign-gallery-item').remove();
                });
                payload.deleted.forEach((path) => {
                    Array.from(document.querySelectorAll('input')).filter((input) => input.value === path).forEach((input) => {
                        if (input.name === 'review_image_existing[]') {
                            input.closest('.campaign-existing-review-item')?.remove();
                        } else {
                            input.value = '';
                        }
                    });
                });
                showGalleryMessage(payload.message);
            } catch (error) {
                showGalleryMessage(error.message, 'error');
            } finally {
                this.disabled = false;
            }
        });

        document.addEventListener('click', (event) => {
            if (event.target.closest('.campaign-existing-review-item button')) {
                event.target.closest('.campaign-existing-review-item').remove();
            }
        });
    })();
</script>
