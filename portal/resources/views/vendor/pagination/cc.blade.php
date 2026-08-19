@if ($paginator->hasPages())
<nav class="cc-pager" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
    @if ($paginator->onFirstPage())
        <span class="btn ghost sm" style="opacity:.45;pointer-events:none">« Prev</span>
    @else
        <a class="btn ghost sm" href="{{ $paginator->previousPageUrl() }}" rel="prev">« Prev</a>
    @endif

    @isset($elements)
    @foreach ($elements as $element)
        @if (is_string($element))
            <span class="muted" style="padding:0 4px">{{ $element }}</span>
        @endif
        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="btn sm">{{ $page }}</span>
                @else
                    <a class="btn ghost sm" href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach
    @endisset

    @if ($paginator->hasMorePages())
        <a class="btn ghost sm" href="{{ $paginator->nextPageUrl() }}" rel="next">Next »</a>
    @else
        <span class="btn ghost sm" style="opacity:.45;pointer-events:none">Next »</span>
    @endif
</nav>
@endif
