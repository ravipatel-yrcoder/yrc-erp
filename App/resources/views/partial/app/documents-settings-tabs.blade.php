<?php $documentsTabPath = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'); ?>

<ul class="nav nav-pills mb-4">
    <li class="nav-item">
        <a class="nav-link {{ str_starts_with($documentsTabPath, '/settings/documents/templates') ? 'active' : '' }}"
           href="/settings/documents/templates/">Templates</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ str_starts_with($documentsTabPath, '/settings/documents/sequences') ? 'active' : '' }}"
           href="/settings/documents/sequences/">Sequences</a>
    </li>
</ul>
