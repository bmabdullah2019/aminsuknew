<li class="tree-item" data-node-id="{{ $head->HeadId }}">
    <div
        class="tree-row"
        data-head-id="{{ $head->HeadId }}"
        data-parent-id="{{ $head->ParentId }}"
        data-acc-type="{{ $head->AccType }}"
        data-head-code="{{ $head->HeadCode }}"
        data-head-name="{{ $head->HeadName }}"
        data-label="{{ $head->Label }}"
        data-description="{{ $head->Description }}"
        data-parent-name="{{ trim((string) $head->ParentHead, ' /') }}"
        data-has-child="{{ $head->children->isNotEmpty() ? '1' : '0' }}"
    >
        @if($head->children->isNotEmpty())
            <button type="button" class="tree-toggle is-open" aria-label="Collapse branch">
                <span class="toggle-icon">-</span>
            </button>
        @else
            <span class="tree-spacer"></span>
        @endif

        <button type="button" class="tree-label">
            <span class="tree-text">
                <span class="tree-name">{{ $head->HeadName }}</span>
                <span class="tree-code">{{ $head->HeadCode }}</span>
            </span>
        </button>

        <div class="tree-actions">
            <button type="button" class="btn btn-xs btn-soft-primary btn-edit-head" title="Edit account">
                <i class="mdi mdi-pencil"></i>
            </button>
            <button type="button" class="btn btn-xs btn-soft-success btn-add-child" title="Add child" data-parent-id="{{ $head->HeadId }}">
                <i class="mdi mdi-plus"></i>
            </button>
            @if($head->ParentId > 0)
                <button type="button" class="btn btn-xs btn-soft-danger btn-delete-head" title="Delete account" data-head-id="{{ $head->HeadId }}">
                    <i class="mdi mdi-delete"></i>
                </button>
            @endif
        </div>
    </div>

    @if($head->children->isNotEmpty())
        <ul class="tree-children">
            @foreach($head->children as $child)
                @include('backEnd.accounts.head._tree_node', ['head' => $child])
            @endforeach
        </ul>
    @endif
</li>
