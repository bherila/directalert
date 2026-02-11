<div class="mb-6">
    <div class="flex justify-between items-center">
        <div class="flex space-x-4">
            <a href="{{ url('/admin/export') }}" 
               class="px-4 py-2 rounded-t-lg {{ request()->is('admin/export') ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300 text-gray-700' }}">
                Export
            </a>
            <a href="{{ url('/admin/import') }}" 
               class="px-4 py-2 rounded-t-lg {{ request()->is('admin/import') ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300 text-gray-700' }}">
                Import
            </a>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="inline">
            @csrf
            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition-colors">
                Log out
            </button>
        </form>
    </div>
    <div class="border-b border-gray-300 -mt-px"></div>
</div>