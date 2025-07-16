@props(['name', 'label' => '', 'type' => 'text', 'required' => false, 'class' => ''])

<div class="{{ $class }}">
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
        {{ $label ?: ucfirst(str_replace('_', ' ', $name)) }}
    </label>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        {{ $required ? 'required' : '' }}
        value="{{ old($name) }}"
        {{ $attributes->merge([
            'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:text-white'
        ]) }}
    >
    @error($name)
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>
