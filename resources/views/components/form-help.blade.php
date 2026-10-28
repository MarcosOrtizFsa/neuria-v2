@props(['text', 'placement' => 'top'])

<button
    type="button"
    class="form-help-button"
    data-bs-toggle="tooltip"
    data-bs-trigger="hover focus"
    data-bs-placement="{{ $placement }}"
    data-bs-title="{{ $text }}"
    aria-label="Ayuda: {{ $text }}"
>
    <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
        <circle cx="12" cy="12" r="9"></circle>
        <path d="M9.8 9a2.3 2.3 0 1 1 3.7 1.8c-.9.6-1.5 1.1-1.5 2.2"></path>
        <circle class="form-help-dot" cx="12" cy="16.8" r=".8"></circle>
    </svg>
</button>
