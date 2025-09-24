<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Features - CueSports Kenya</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'cue-green': '#1B4332',
                        'cue-green-light': '#2D5A3D',
                        'cue-green-dark': '#081C15',
                    }
                }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav id="navbar" class="bg-cue-green-dark fixed w-full z-50 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <h1 id="nav-logo" class="text-2xl font-bold text-white transition-colors duration-300">CueSports Kenya</h1>
                    </div>
                </div>
                <div class="hidden md:flex items-center space-x-8">
                    <a href="/" id="nav-link-1" class="text-white hover:text-green-300 transition duration-300">Home</a>
                    <a href="/features" id="nav-link-2" class="text-white hover:text-green-300 transition duration-300">Features</a>
                    <a href="/about" id="nav-link-3" class="text-white hover:text-green-300 transition duration-300">About</a>
                    <a href="/contact" id="nav-link-4" class="text-white hover:text-green-300 transition duration-300">Contact</a>
                    <a href="/blog" id="nav-blog" class="bg-white text-cue-green px-4 py-2 rounded-lg hover:bg-gray-100 transition duration-300">Blog</a>
                </div>
                <div class="md:hidden flex items-center">
                    <button id="mobile-menu-btn" class="text-white hover:text-green-300">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
            
            <!-- Mobile Menu -->
            <div id="mobile-menu" class="md:hidden hidden">
                <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg-cue-green-dark">
                    <a href="/" class="text-white hover:text-green-300 block px-3 py-2 text-base font-medium">Home</a>
                    <a href="/features" class="text-white hover:text-green-300 block px-3 py-2 text-base font-medium">Features</a>
                    <a href="/about" class="text-white hover:text-green-300 block px-3 py-2 text-base font-medium">About</a>
                    <a href="/contact" class="text-white hover:text-green-300 block px-3 py-2 text-base font-medium">Contact</a>
                    <a href="/blog" class="bg-white text-cue-green block px-3 py-2 text-base font-medium rounded-lg mx-3 text-center">Blog</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Features Content -->
    <main class="pt-16 bg-white min-h-screen">
        <!-- Header Section -->
        <section class="py-20 bg-cue-green-dark">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h1 class="text-5xl font-bold text-white mb-6">System Features</h1>
                    <p class="text-xl text-gray-300 leading-relaxed">
                        Discover the comprehensive features that make CueSports Kenya the premier 
                        pool management ecosystem in East Africa.
                    </p>
                </div>
            </div>
        </section>

        <!-- Core Features Section -->
        <section class="py-16 bg-white">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Tournament Management -->
                <div class="mb-20">
                    <div class="text-center mb-12">
                        <div class="bg-cue-green text-white w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-trophy text-3xl"></i>
                        </div>
                        <h2 class="text-4xl font-bold text-gray-900 mb-4">Tournament Management</h2>
                        <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                            Comprehensive tournament organization from community to national level
                        </p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <div class="text-center">
                            <h3 class="text-xl font-bold text-gray-900 mb-3">Multi-Level Tournaments</h3>
                            <p class="text-gray-600">
                                Community, county, regional, and national level competitions with structured progression pathways.
                            </p>
                        </div>
                        <div class="text-center">
                            <h3 class="text-xl font-bold text-gray-900 mb-3">Smart Player Pairing</h3>
                            <p class="text-gray-600">
                                Intelligent matching system that pairs players based on skill level, location, and availability.
                            </p>
                        </div>
                        <div class="text-center">
                            <h3 class="text-xl font-bold text-gray-900 mb-3">Real-Time Updates</h3>
                            <p class="text-gray-600">
                                Live match results, tournament brackets, and instant notifications for all participants.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Career Development -->
                <div class="mb-20">
                    <div class="text-center mb-12">
                        <div class="bg-cue-green text-white w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-chart-line text-3xl"></i>
                        </div>
                        <h2 class="text-4xl font-bold text-gray-900 mb-4">Career Development</h2>
                        <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                            Professional pathways and opportunities for talented pool players
                        </p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <div class="text-center">
                            <h3 class="text-xl font-bold text-gray-900 mb-3">Player Rankings</h3>
                            <p class="text-gray-600">
                                Comprehensive ranking system tracking performance across all tournament levels.
                            </p>
                        </div>
                        <div class="text-center">
                            <h3 class="text-xl font-bold text-gray-900 mb-3">Performance Analytics</h3>
                            <p class="text-gray-600">
                                Detailed statistics and insights to help players improve their game and track progress.
                            </p>
                        </div>
                        <div class="text-center">
                            <h3 class="text-xl font-bold text-gray-900 mb-3">Achievement System</h3>
                            <p class="text-gray-600">
                                Recognition badges, titles, and awards for tournament victories and milestones.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Community Features -->
                <div class="mb-20">
                    <div class="text-center mb-12">
                        <div class="bg-cue-green text-white w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-users text-3xl"></i>
                        </div>
                        <h2 class="text-4xl font-bold text-gray-900 mb-4">Community Building</h2>
                        <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                            Connecting pool players and building a vibrant community across Kenya
                        </p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <div class="text-center">
                            <h3 class="text-xl font-bold text-gray-900 mb-3">Player Profiles</h3>
                            <p class="text-gray-600">
                                Comprehensive profiles showcasing achievements, statistics, and tournament history.
                            </p>
                        </div>
                        <div class="text-center">
                            <h3 class="text-xl font-bold text-gray-900 mb-3">Venue Network</h3>
                            <p class="text-gray-600">
                                Partnership with pool halls across Kenya providing tournament venues and practice facilities.
                            </p>
                        </div>
                        <div class="text-center">
                            <h3 class="text-xl font-bold text-gray-900 mb-3">Social Features</h3>
                            <p class="text-gray-600">
                                Player messaging, match coordination, and community interaction tools.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Professional Standards -->
                <div class="mb-20">
                    <div class="text-center mb-12">
                        <div class="bg-cue-green text-white w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-medal text-3xl"></i>
                        </div>
                        <h2 class="text-4xl font-bold text-gray-900 mb-4">Professional Standards</h2>
                        <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                            World-class tournament management with integrity and transparency
                        </p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <div class="text-center">
                            <h3 class="text-xl font-bold text-gray-900 mb-3">Standardized Rules</h3>
                            <p class="text-gray-600">
                                Consistent tournament rules and regulations across all levels and regions.
                            </p>
                        </div>
                        <div class="text-center">
                            <h3 class="text-xl font-bold text-gray-900 mb-3">Fair Play Monitoring</h3>
                            <p class="text-gray-600">
                                Advanced systems to ensure fair play and maintain tournament integrity.
                            </p>
                        </div>
                        <div class="text-center">
                            <h3 class="text-xl font-bold text-gray-900 mb-3">Transparent Results</h3>
                            <p class="text-gray-600">
                                Open and transparent result reporting with detailed match histories.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Mobile Platform -->
                <div class="mb-20">
                    <div class="text-center mb-12">
                        <div class="bg-cue-green text-white w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-mobile-alt text-3xl"></i>
                        </div>
                        <h2 class="text-4xl font-bold text-gray-900 mb-4">Mobile Platform</h2>
                        <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                            Access everything on the go with our comprehensive mobile application
                        </p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <div class="text-center">
                            <h3 class="text-xl font-bold text-gray-900 mb-3">Cross-Platform</h3>
                            <p class="text-gray-600">
                                Available on both iOS and Android with synchronized data across devices.
                            </p>
                        </div>
                        <div class="text-center">
                            <h3 class="text-xl font-bold text-gray-900 mb-3">Offline Support</h3>
                            <p class="text-gray-600">
                                Core features work offline with automatic sync when connection is restored.
                            </p>
                        </div>
                        <div class="text-center">
                            <h3 class="text-xl font-bold text-gray-900 mb-3">Push Notifications</h3>
                            <p class="text-gray-600">
                                Instant notifications for match pairings, results, and tournament updates.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Call to Action -->
                <div class="text-center bg-gray-50 rounded-2xl p-12">
                    <h2 class="text-3xl font-bold text-gray-900 mb-6">Ready to Experience These Features?</h2>
                    <p class="text-xl text-gray-600 mb-8">
                        Join thousands of pool players across Kenya who are already using our platform 
                        to compete, improve, and build their careers.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="#" class="bg-black text-white px-8 py-4 rounded-lg flex items-center justify-center hover:bg-gray-800 transition duration-300">
                            <i class="fab fa-apple mr-3 text-xl"></i>
                            <div>
                                <div class="text-xs">Download on the</div>
                                <div class="text-lg font-semibold">App Store</div>
                            </div>
                        </a>
                        <a href="#" class="bg-black text-white px-8 py-4 rounded-lg flex items-center justify-center hover:bg-gray-800 transition duration-300">
                            <i class="fab fa-google-play mr-3 text-xl"></i>
                            <div>
                                <div class="text-xs">Get it on</div>
                                <div class="text-lg font-semibold">Google Play</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-cue-green-dark text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                <div>
                    <h3 class="text-2xl font-bold mb-4">CueSports Kenya</h3>
                    <p class="text-gray-300 mb-6">
                        Kenya's premier pool tournament management platform. 
                        Connecting players nationwide through organized competition.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-300 hover:text-white transition duration-300">
                            <i class="fab fa-twitter text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-300 hover:text-white transition duration-300">
                            <i class="fab fa-facebook text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-300 hover:text-white transition duration-300">
                            <i class="fab fa-instagram text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-300 hover:text-white transition duration-300">
                            <i class="fab fa-linkedin text-xl"></i>
                        </a>
                    </div>
                </div>
                
                <div>
                    <h4 class="text-xl font-bold mb-6">Get the app</h4>
                    <div class="space-y-4">
                        <a href="#" class="bg-black text-white px-6 py-3 rounded-lg flex items-center hover:bg-gray-800 transition duration-300 w-fit">
                            <i class="fab fa-apple mr-3 text-xl"></i>
                            <div>
                                <div class="text-xs">Download on the</div>
                                <div class="text-lg font-semibold">App Store</div>
                            </div>
                        </a>
                        <a href="#" class="bg-black text-white px-6 py-3 rounded-lg flex items-center hover:bg-gray-800 transition duration-300 w-fit">
                            <i class="fab fa-google-play mr-3 text-xl"></i>
                            <div>
                                <div class="text-xs">Get it on</div>
                                <div class="text-lg font-semibold">Google Play</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-700 mt-12 pt-8">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="flex space-x-6 mb-4 md:mb-0">
                        <a href="/" class="text-gray-300 hover:text-white transition duration-300">Home</a>
                        <a href="/features" class="text-gray-300 hover:text-white transition duration-300">Features</a>
                        <a href="/about" class="text-gray-300 hover:text-white transition duration-300">About</a>
                        <a href="/contact" class="text-gray-300 hover:text-white transition duration-300">Contact</a>
                        <a href="/blog" class="text-gray-300 hover:text-white transition duration-300">Blog</a>
                    </div>
                    <p class="text-gray-400 text-sm">
                        © 2024 CueSports Kenya. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Navigation Script -->
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            const logo = document.getElementById('nav-logo');
            const navLinks = document.querySelectorAll('#navbar a[id^="nav-link"]');
            const blogBtn = document.getElementById('nav-blog');
            const mobileBtn = document.getElementById('mobile-menu-btn');
            
            if (window.scrollY > 50) {
                // Scrolled state - white background, dark text
                navbar.className = 'bg-white shadow-lg fixed w-full z-50 transition-all duration-300';
                logo.className = 'text-2xl font-bold text-cue-green transition-colors duration-300';
                navLinks.forEach(link => {
                    link.className = 'text-gray-700 hover:text-cue-green transition duration-300';
                });
                blogBtn.className = 'bg-cue-green text-white px-4 py-2 rounded-lg hover:bg-cue-green-light transition duration-300';
                if (mobileBtn) {
                    mobileBtn.className = 'text-gray-700 hover:text-cue-green';
                }
            } else {
                // Top state - green background, white text
                navbar.className = 'bg-cue-green-dark fixed w-full z-50 transition-all duration-300';
                logo.className = 'text-2xl font-bold text-white transition-colors duration-300';
                navLinks.forEach(link => {
                    link.className = 'text-white hover:text-green-300 transition duration-300';
                });
                blogBtn.className = 'bg-white text-cue-green px-4 py-2 rounded-lg hover:bg-gray-100 transition duration-300';
                if (mobileBtn) {
                    mobileBtn.className = 'text-white hover:text-green-300';
                }
            }
        });

        // Mobile menu toggle
        const mobileMenuButton = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        
        if (mobileMenuButton && mobileMenu) {
            mobileMenuButton.addEventListener('click', function() {
                mobileMenu.classList.toggle('hidden');
            });
        }
    </script>
</body>
</html>
