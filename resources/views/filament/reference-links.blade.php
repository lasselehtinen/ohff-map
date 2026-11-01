<div style="display: flex; flex-direction: column; align-items: flex-start; gap: 0.25rem;">
    @foreach ($getRecord()->adminLinks() as $label => $url)
        <x-filament::link :href="$url" target="_blank" rel="noopener noreferrer">
            {{ $label }}
        </x-filament::link>
    @endforeach
</div>
