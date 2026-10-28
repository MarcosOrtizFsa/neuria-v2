import 'bootstrap';
import { Tooltip } from 'bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
        Tooltip.getOrCreateInstance(element);
    });
});
