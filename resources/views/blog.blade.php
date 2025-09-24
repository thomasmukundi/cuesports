<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pool Culture & Career Development - CueSports Kenya</title>
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

    <!-- Blog Content -->
    <main class="pt-16 bg-white min-h-screen">
        <!-- Header Section -->
        <section class="py-20 bg-cue-green-dark">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h1 class="text-5xl font-bold text-white mb-6">Building Kenya's Pool Culture</h1>
                    <p class="text-xl text-gray-300 leading-relaxed">
                        Transforming pool from a casual pastime into a structured pathway for talent development, 
                        career opportunities, and community building across Kenya.
                    </p>
                </div>
            </div>
        </section>

        <!-- Main Content -->
        <section class="py-16 bg-white">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Vision Section -->
                <div class="mb-16">
                    <h2 class="text-3xl font-bold text-gray-900 mb-8">Our Vision for Pool in Kenya</h2>
                    <div class="prose prose-lg max-w-none">
                        <p class="text-lg text-gray-700 leading-relaxed mb-6">
                            Pool has long been viewed as merely a recreational activity in Kenya. We believe it's time to change that narrative. 
                            Our mission is to establish a comprehensive ecosystem that recognizes pool as a legitimate sport with real career 
                            potential for talented players across the country.
                        </p>
                        <p class="text-lg text-gray-700 leading-relaxed mb-6">
                            Through structured tournaments, professional development programs, and community engagement initiatives, 
                            we're creating pathways for players to transform their passion into sustainable careers while building 
                            a vibrant pool culture that spans from local communities to national championships.
                        </p>
                    </div>
                </div>

                <!-- Career Development Section -->
                <div class="mb-16">
                    <h2 class="text-3xl font-bold text-gray-900 mb-8">Creating Career Opportunities</h2>
                    <div class="prose prose-lg max-w-none">
                        <p class="text-lg text-gray-700 leading-relaxed mb-6">
                            Every talented pool player deserves the opportunity to pursue their passion professionally. 
                            We're establishing multiple career pathways within the pool ecosystem:
                        </p>
                        
                        <h3 class="text-2xl font-semibold text-gray-900 mb-4 mt-8">Professional Tournament Circuit</h3>
                        <p class="text-lg text-gray-700 leading-relaxed mb-6">
                            Our structured tournament system progresses from community level to national championships, 
                            providing prize money, sponsorship opportunities, and recognition for top performers. 
                            Players can build rankings, attract sponsors, and compete for substantial rewards.
                        </p>

                        <h3 class="text-2xl font-semibold text-gray-900 mb-4 mt-8">Coaching and Mentorship Programs</h3>
                        <p class="text-lg text-gray-700 leading-relaxed mb-6">
                            Experienced players can transition into coaching roles, sharing their expertise with the next generation. 
                            We facilitate mentorship programs that pair seasoned professionals with emerging talent, 
                            creating income opportunities while developing the sport.
                        </p>

                        <h3 class="text-2xl font-semibold text-gray-900 mb-4 mt-8">Tournament Organization and Management</h3>
                        <p class="text-lg text-gray-700 leading-relaxed mb-6">
                            The growing tournament circuit requires skilled organizers, referees, and administrators. 
                            We provide training and certification programs for these essential roles, creating employment 
                            opportunities within the pool management ecosystem.
                        </p>
                    </div>
                </div>

                <!-- Community Building Section -->
                <div class="mb-16">
                    <h2 class="text-3xl font-bold text-gray-900 mb-8">Building Strong Pool Communities</h2>
                    <div class="prose prose-lg max-w-none">
                        <p class="text-lg text-gray-700 leading-relaxed mb-6">
                            A thriving pool culture requires strong communities at every level. We're fostering connections 
                            and collaboration across Kenya's pool landscape:
                        </p>

                        <h3 class="text-2xl font-semibold text-gray-900 mb-4 mt-8">Local Pool Halls and Venues</h3>
                        <p class="text-lg text-gray-700 leading-relaxed mb-6">
                            We partner with pool halls across Kenya to host tournaments, training sessions, and community events. 
                            These venues become hubs of activity, bringing players together and creating vibrant local scenes 
                            that contribute to the broader pool culture.
                        </p>

                        <h3 class="text-2xl font-semibold text-gray-900 mb-4 mt-8">Player Networks and Support Systems</h3>
                        <p class="text-lg text-gray-700 leading-relaxed mb-6">
                            Our platform connects players across different regions, facilitating knowledge sharing, 
                            friendly competition, and mutual support. Players can find training partners, share strategies, 
                            and build lasting friendships that strengthen the entire community.
                        </p>

                        <h3 class="text-2xl font-semibold text-gray-900 mb-4 mt-8">Youth Development Programs</h3>
                        <p class="text-lg text-gray-700 leading-relaxed mb-6">
                            We're investing in the future by establishing youth programs in schools and communities. 
                            Young players receive proper training, mentorship, and opportunities to compete, 
                            ensuring the continued growth and evolution of Kenya's pool culture.
                        </p>
                    </div>
                </div>

                <!-- Professional Standards Section -->
                <div class="mb-16">
                    <h2 class="text-3xl font-bold text-gray-900 mb-8">Establishing Professional Standards</h2>
                    <div class="prose prose-lg max-w-none">
                        <p class="text-lg text-gray-700 leading-relaxed mb-6">
                            To elevate pool to professional sport status, we maintain rigorous standards across all aspects 
                            of tournament management and player development:
                        </p>

                        <h3 class="text-2xl font-semibold text-gray-900 mb-4 mt-8">Tournament Integrity</h3>
                        <p class="text-lg text-gray-700 leading-relaxed mb-6">
                            Every tournament follows standardized rules, fair pairing systems, and transparent result reporting. 
                            We ensure that competitions are conducted with the highest level of professionalism, 
                            building trust and credibility within the pool community.
                        </p>

                        <h3 class="text-2xl font-semibold text-gray-900 mb-4 mt-8">Player Recognition and Rankings</h3>
                        <p class="text-lg text-gray-700 leading-relaxed mb-6">
                            We maintain comprehensive player statistics, rankings, and achievement records. 
                            This system recognizes excellence, tracks progress, and provides the foundation for 
                            sponsorship opportunities and professional advancement.
                        </p>

                        <h3 class="text-2xl font-semibold text-gray-900 mb-4 mt-8">Continuous Innovation</h3>
                        <p class="text-lg text-gray-700 leading-relaxed mb-6">
                            We continuously evolve our systems based on player feedback, international best practices, 
                            and emerging technologies. This commitment to innovation ensures that Kenya's pool scene 
                            remains dynamic, engaging, and aligned with global standards.
                        </p>
                    </div>
                </div>

                <!-- Call to Action Section -->
                <div class="mb-16 bg-gray-50 rounded-2xl p-8">
                    <h2 class="text-3xl font-bold text-gray-900 mb-6 text-center">Join the Movement</h2>
                    <p class="text-lg text-gray-700 leading-relaxed mb-8 text-center">
                        Whether you're a seasoned player, an aspiring professional, or someone passionate about developing 
                        Kenya's sporting culture, there's a place for you in our growing community.
                    </p>
                    <div class="text-center">
                        <a href="/" class="bg-cue-green text-white px-8 py-4 rounded-lg text-lg font-semibold hover:bg-cue-green-light transition duration-300 inline-block">
                            Get Started Today
                        </a>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="text-center">
                    <h3 class="text-2xl font-semibold text-gray-900 mb-4">Ready to Transform Your Pool Career?</h3>
                    <p class="text-lg text-gray-600 mb-6">
                        Connect with us to learn more about tournament opportunities, coaching programs, 
                        and how you can contribute to Kenya's growing pool culture.
                    </p>
                    <div class="flex justify-center space-x-6">
                        <a href="mailto:info@cuesportskenya.com" class="text-cue-green hover:text-cue-green-light transition duration-300">
                            <i class="fas fa-envelope text-xl mr-2"></i>
                            Email Us
                        </a>
                        <a href="tel:+254700000000" class="text-cue-green hover:text-cue-green-light transition duration-300">
                            <i class="fas fa-phone text-xl mr-2"></i>
                            Call Us
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
                        <a href="/blog" class="text-gray-300 hover:text-white transition duration-300">Blog</a>
                        <a href="#" class="text-gray-300 hover:text-white transition duration-300">Privacy Policy</a>
                        <a href="#" class="text-gray-300 hover:text-white transition duration-300">Terms of Service</a>
                        <a href="#" class="text-gray-300 hover:text-white transition duration-300">Contact</a>
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

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
