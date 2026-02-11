@extends('layout.master_layout')

@section('title', 'Admin Import')

@section('content')
<div class="w-full mx-auto sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <x-admin-tab-bar />
            
            <h1 class="text-2xl font-bold mb-6 dark:text-white">Import Direct Alert Data</h1>

            <form id="upload-form" method="POST" action="{{ url('/admin/import') }}" enctype="multipart/form-data">
                @csrf
                <div id="drop-zone" class="border-2 border-dashed border-gray-400 rounded-lg p-8 text-center cursor-pointer hover:border-blue-500 transition-colors">
                    <div class="text-gray-500 dark:text-gray-400">
                        <svg class="mx-auto h-12 w-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        <p class="mb-2">Drop your CSV file here or click to select</p>
                        <p class="text-sm">Supported columns: PERSON, ACCT_ID, POSTAL</p>
                    </div>
                    <input type="file" id="file-input" name="csv_file" class="hidden" accept=".csv">
                </div>

                <div id="file-info" class="mt-4 p-4 rounded hidden">
                    <p class="text-gray-700 dark:text-gray-300"></p>
                </div>

                <div id="preview-container" class="mt-6 hidden">
                    <h2 class="text-lg font-semibold mb-4 dark:text-white">Data Preview</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr id="preview-headers"></tr>
                            </thead>
                            <tbody id="preview-body" class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-6">
                        <button type="submit" id="submit-button" disabled class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition-colors opacity-50 cursor-not-allowed">
                            Upload and Import
                        </button>
                    </div>
                </div>
            </form>

            <div id="error-message" class="mt-4 p-4 bg-red-100 text-red-700 rounded hidden"></div>

            @if ($errors->any())
                <div class="mt-4 p-4 bg-red-100 text-red-700 rounded">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('custom_js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('file-input');
    const fileInfo = document.getElementById('file-info');
    const errorMessage = document.getElementById('error-message');
    const previewContainer = document.getElementById('preview-container');
    const previewHeaders = document.getElementById('preview-headers');
    const previewBody = document.getElementById('preview-body');
    const submitButton = document.getElementById('submit-button');
    const uploadForm = document.getElementById('upload-form');

    // Handle drag and drop events
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    // Add visual feedback for drag events
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });

    function highlight(e) {
        dropZone.classList.add('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
    }

    function unhighlight(e) {
        dropZone.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
    }

    // Handle dropped files
    dropZone.addEventListener('drop', handleDrop, false);
    
    function handleDrop(e) {
        const dt = e.dataTransfer;
        const file = dt.files[0];
        handleFile(file);
    }

    // Handle clicked file selection
    dropZone.addEventListener('click', () => {
        fileInput.click();
    });

    fileInput.addEventListener('change', (e) => {
        handleFile(e.target.files[0]);
    });

    function parseCSVLine(text) {
        const result = [];
        let cell = '';
        let inQuotes = false;
        let i = 0;
        
        while (i < text.length) {
            const char = text[i];
            
            if (char === '"') {
                if (inQuotes && text[i + 1] === '"') {
                    // Handle escaped quotes (two quotes in a row)
                    cell += '"';
                    i++;
                } else {
                    // Toggle quote mode
                    inQuotes = !inQuotes;
                }
            } else if (char === ',' && !inQuotes) {
                // End of cell
                result.push(cell.trim());
                cell = '';
            } else {
                cell += char;
            }
            i++;
        }
        
        // Add the last cell
        result.push(cell.trim());
        return result;
    }

    function handleFile(file) {
        if (!file) return;
        
        if (file.type !== 'text/csv' && !file.name.endsWith('.csv')) {
            showError('Please upload a CSV file.');
            return;
        }

        // Show file info
        fileInfo.classList.remove('hidden');
        fileInfo.classList.add('bg-green-100', 'text-green-700');
        fileInfo.querySelector('p').textContent = `Selected file: ${file.name}`;
        errorMessage.classList.add('hidden');

        const reader = new FileReader();
        reader.onload = function(e) {
            const text = e.target.result;
            // Split into lines, handling possible \r\n
            const lines = text.replace(/\r\n/g, '\n').split('\n');
            
            // Parse each line properly handling quotes
            const rows = lines.map(line => parseCSVLine(line));
            
            // Validate header row
            const headers = rows[0];
            const requiredColumns = ['PERSON', 'ACCT_ID', 'POSTAL'];
            
            const missingColumns = requiredColumns.filter(col => !headers.includes(col));
            if (missingColumns.length > 0) {
                showError(`Missing required columns: ${missingColumns.join(', ')}`);
                previewContainer.classList.add('hidden');
                submitButton.disabled = true;
                submitButton.classList.add('opacity-50', 'cursor-not-allowed');
                return;
            }

            // Show preview
            previewContainer.classList.remove('hidden');
            
            // Create header row
            previewHeaders.innerHTML = headers.map(header => `
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    ${header}
                    ${requiredColumns.includes(header) ? 
                        '<span class="text-green-500 ml-1">✓</span>' : 
                        '<span class="text-gray-400 ml-1">(ignored)</span>'}
                </th>
            `).join('');

            // Show first 5 rows of data
            const previewRows = rows.slice(1, 6).filter(row => row.some(cell => cell.trim()));
            previewBody.innerHTML = previewRows.map(row => `
                <tr>
                    ${row.map(cell => `
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                            ${cell}
                        </td>
                    `).join('')}
                </tr>
            `).join('');

            // Enable submit button
            submitButton.disabled = false;
            submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
        };
        reader.readAsText(file);
    }

    function showError(message) {
        errorMessage.textContent = message;
        errorMessage.classList.remove('hidden');
        fileInfo.classList.add('hidden');
    }

    // Handle form submission
    uploadForm.addEventListener('submit', function(e) {
        if (submitButton.disabled) {
            e.preventDefault();
            return false;
        }
    });
});
</script>
@endsection