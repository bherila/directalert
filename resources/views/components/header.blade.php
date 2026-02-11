<header class="w-full">
    <!-- Top Bar with teal background -->
    <div class="bg-teal-500 text-white py-3">
        <div class="container mx-auto px-4 flex flex-wrap items-center justify-between">
            <!-- Left utility links -->
            <div class="flex items-center space-x-6">
                <a href="http://investors.middlesexwater.com/" target="_blank" class="text-white hover:underline">Investors</a>
                <a href="https://www.middlesexwater.com/developers-partners/" class="text-white hover:underline">Developers &amp; Partners</a>
                <a href="https://www.middlesexwater.com/contact-us/" class="text-white hover:underline">Contact Us</a>
            </div>
            
            <!-- Center search form -->
            <div class="relative mx-4 lg:w-1/4">
                <form action="https://www.middlesexwater.com" method="get" class="flex items-center">
                    <div class="relative w-full">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="search" name="s" placeholder="Search..." class="w-full py-1.5 pl-10 pr-4 text-gray-700 bg-white border rounded-md focus:outline-none">
                    </div>
                </form>
            </div>
            
            <!-- Our Companies dropdown -->
            <div class="relative" id="our-companies-container">
                <button id="our-companies-toggle" class="flex items-center text-white focus:outline-none hover:text-teal-300">
                    Our Companies
                    <svg class="w-5 h-5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div id="our-companies-menu" class="absolute right-0 z-10 hidden w-64 mt-2 bg-white rounded-md shadow-lg">
                    <div class="py-1">
                        <a href="https://www.middlesexwater.com/middlesex-water-company/" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Middlesex Water Company (including Fortescue System)</a>
                        <a href="https://www.middlesexwater.com/tidewater-utilities/" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Tidewater Utilities, Inc.</a>
                        <a href="https://www.middlesexwater.com/pinelands-water-and-wastewater-company/" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Pinelands Water and Wastewater Company</a>
                        <a href="https://www.middlesexwater.com/utility-service-affiliates-perth-amboy-inc/" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Utility Service Affiliates (Perth Amboy), Inc.</a>
                        <a href="https://www.middlesexwater.com/utility-service-affiliates-avalon/" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Utility Service Affiliates (Avalon)</a>
                        <a href="https://www.middlesexwater.com/utility-service-affiliates-inc-highland-park/" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Utility Service Affiliates, Inc. (Highland Park)</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main navigation with logo and menu -->
    <div class="bg-white shadow-sm py-4">
        <div class="container mx-auto px-4 flex flex-wrap items-center justify-between">
            <!-- Logo -->
            <div class="flex items-center">
                <a href="https://www.middlesexwater.com" class="block">
                    <img src="https://www.middlesexwater.com/wp-content/uploads/2018/06/MWC_Blue-680x308.png" alt="Middlesex Water Company" class="h-16">
                </a>
            </div>
            
            <!-- Main Navigation Menu -->
            <div class="hidden lg:flex items-center space-x-8">
                <a href="https://www.middlesexwater.com/about-us/" class="text-gray-700 font-medium uppercase hover:text-teal-500">About Us</a>
                <a href="https://www.middlesexwater.com/tips-tools/" class="text-gray-700 font-medium uppercase hover:text-teal-500">Tips &amp; Tools</a>
                <a href="https://www.middlesexwater.com/news-room/" class="text-gray-700 font-medium uppercase hover:text-teal-500">News Room</a>
                <a href="https://www.middlesexwater.com/careers/" class="text-gray-700 font-medium uppercase hover:text-teal-500">Careers</a>
                <a href="https://www.middlesexwater.com/alerts/" class="text-gray-700 font-medium uppercase hover:text-teal-500">Alerts</a>
            </div>
            
            <!-- Payment Options Button -->
            <div>
                <a href="https://www.middlesexwater.com/customer-care/payment-options/" target="_blank" class="bg-lime-500 hover:bg-lime-600 text-white font-medium py-3 px-6 rounded transition duration-200 uppercase">
                    Payment Options
                </a>
            </div>
            
            <!-- Mobile menu button - hidden on desktop -->
            <div class="lg:hidden">
                <button id="mobile-menu-toggle" class="text-gray-700 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Mobile menu - hidden by default, shown when toggled -->
        <div id="mobile-menu" class="hidden lg:hidden container mx-auto px-4 pt-2 pb-4">
            <div class="space-y-2">
                <a href="https://www.middlesexwater.com/about-us/" class="block text-gray-700 font-medium py-2">About Us</a>
                <a href="https://www.middlesexwater.com/tips-tools/" class="block text-gray-700 font-medium py-2">Tips &amp; Tools</a>
                <a href="https://www.middlesexwater.com/news-room/" class="block text-gray-700 font-medium py-2">News Room</a>
                <a href="https://www.middlesexwater.com/careers/" class="block text-gray-700 font-medium py-2">Careers</a>
                <a href="https://www.middlesexwater.com/alerts/" class="block text-gray-700 font-medium py-2">Alerts</a>
            </div>
        </div>
    </div>
    
    <!-- Bottom border -->
    <div class="border-b border-gray-200"></div>
</header>

<script>
    // Toggle mobile menu
    $('#mobile-menu-toggle').on('click', function() {
        $('#mobile-menu').toggleClass('hidden');
    });

    // Companies dropdown functionality with hover intent
    const $companiesContainer = $('#our-companies-container');
    const $companiesMenu = $('#our-companies-menu');
    
    let hoverTimeout;
    
    // Desktop hover functionality
    $companiesContainer.hover(
        function() {
            // Mouse enter
            clearTimeout(hoverTimeout);
            $companiesMenu.removeClass('hidden');
        },
        function() {
            // Mouse leave with delay
            hoverTimeout = setTimeout(function() {
                $companiesMenu.addClass('hidden');
            }, 300);
        }
    );
    
    // Mobile click functionality
    $('#our-companies-toggle').on('click', function(e) {
        e.preventDefault();
        $companiesMenu.toggleClass('hidden');
        e.stopPropagation();
    });
    
    // Close on click outside
    $(document).on('click', function(e) {
        if (!$companiesContainer.is(e.target) && $companiesContainer.has(e.target).length === 0) {
            $companiesMenu.addClass('hidden');
        }
    });
</script>