@props(['title', 'value'])

<div class="bg-white dark:bg-gray-800 p-4 shadow rounded-lg">
    <div class="text-sm text-gray-500 dark:text-gray-300 mb-1">{{ $title }}</div>
    <div class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $value }}</div>
</div>
