<img
    src="{{ $src }}"
    alt="{{ $alt }}"
    @if($width) width="{{ $width }}" @endif
    @if($height) height="{{ $height }}" @endif
    @if($lazy) loading="lazy" @endif
    @if($srcset) srcset="{{ $srcset }}" @endif
    @if($class) class="{{ $class }}" @endif
    {{ $attributes }}
>
