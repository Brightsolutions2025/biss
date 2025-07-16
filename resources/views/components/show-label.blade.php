@props(['label', 'value'])

<div>
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $label }}</label>
    <input type="text" disabled value="{{ $value ?? '-' }}"
        class="mt-1 block w-full rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-white">
</div>