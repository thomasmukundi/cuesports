<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - CueSports Kenya</title>
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
    <meta name="csrf-token" content="{{ csrf_token() }}">
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

    <!-- Contact Content -->
    <main class="pt-16 bg-white min-h-screen">
        <!-- Header Section -->
        <section class="py-20 bg-cue-green-dark">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h1 class="text-5xl font-bold text-white mb-6">Contact Us</h1>
                    <p class="text-xl text-gray-300 leading-relaxed">
                        Get in touch with us. We'd love to hear from you and help you 
                        with any questions about CueSports Kenya.
                    </p>
                </div>
            </div>
        </section>

        <!-- Contact Form Section -->
        <section class="py-16 bg-white">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Contact Information -->
                <div class="mb-16">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div class="text-center">
                            <div class="bg-cue-green text-white w-16 h-16 rounded-xl flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-envelope text-2xl"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Email Us</h3>
                            <p class="text-gray-600">info@cuesportskenya.com</p>
                            <p class="text-gray-600">support@cuesportskenya.com</p>
                        </div>
                        
                        <div class="text-center">
                            <div class="bg-cue-green text-white w-16 h-16 rounded-xl flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-phone text-2xl"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Call Us</h3>
                            <p class="text-gray-600">+254 700 000 000</p>
                            <p class="text-gray-600">+254 711 000 000</p>
                        </div>
                        
                        <div class="text-center">
                            <div class="bg-cue-green text-white w-16 h-16 rounded-xl flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-map-marker-alt text-2xl"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Visit Us</h3>
                            <p class="text-gray-600">Nairobi, Kenya</p>
                            <p class="text-gray-600">Available Nationwide</p>
                        </div>
                    </div>
                </div>

                <!-- Contact Form -->
                <div class="bg-gray-50 rounded-2xl p-8 lg:p-12">
                    <div class="text-center mb-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-4">Send Us a Message</h2>
                        <p class="text-lg text-gray-600">
                            Fill out the form below and we'll get back to you as soon as possible.
                        </p>
                    </div>
                    
                    <form id="contactForm" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Full Name *
                                </label>
                                <input 
                                    type="text" 
                                    id="name" 
                                    name="name" 
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cue-green focus:border-transparent transition duration-200"
                                    placeholder="Enter your full name"
                                >
                            </div>
                            
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                    Email Address *
                                </label>
                                <input 
                                    type="email" 
                                    id="email" 
                                    name="email" 
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cue-green focus:border-transparent transition duration-200"
                                    placeholder="Enter your email address"
                                >
                            </div>
                        </div>
                        
                        <div>
                            <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">
                                Subject *
                            </label>
                            <input 
                                type="text" 
                                id="subject" 
                                name="subject" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cue-green focus:border-transparent transition duration-200"
                                placeholder="What is this regarding?"
                            >
                        </div>
                        
                        <div>
                            <label for="message" class="block text-sm font-medium text-gray-700 mb-2">
                                Message *
                            </label>
                            <textarea 
                                id="message" 
                                name="message" 
                                rows="6" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cue-green focus:border-transparent transition duration-200 resize-vertical"
                                placeholder="Tell us more about your inquiry..."
                            ></textarea>
                        </div>
                        
                        <div class="text-center">
                            <button 
                                type="submit" 
                                id="submitBtn"
                                class="bg-cue-green text-white px-8 py-4 rounded-lg text-lg font-semibold hover:bg-cue-green-light transition duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span id="submitText">Send Message</span>
                                <i id="submitSpinner" class="fas fa-spinner fa-spin ml-2 hidden"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- FAQ Section -->
                <div class="mt-20">
                    <div class="text-center mb-12">
                        <h2 class="text-3xl font-bold text-gray-900 mb-4">Frequently Asked Questions</h2>
                        <p class="text-lg text-gray-600">
                            Quick answers to common questions
                        </p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 mb-3">How do I join tournaments?</h3>
                            <p class="text-gray-600 mb-6">
                                Download our mobile app, create an account, and browse available tournaments 
                                in your area. Registration is simple and secure.
                            </p>
                            
                            <h3 class="text-xl font-bold text-gray-900 mb-3">What are the tournament fees?</h3>
                            <p class="text-gray-600">
                                Fees vary by tournament level. Community tournaments typically have minimal 
                                entry fees, while regional and national tournaments may have higher fees.
                            </p>
                        </div>
                        
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 mb-3">How can I become a venue partner?</h3>
                            <p class="text-gray-600 mb-6">
                                Contact us through this form or call our partnership team. We're always 
                                looking for quality pool halls to join our network.
                            </p>
                            
                            <h3 class="text-xl font-bold text-gray-900 mb-3">Do you offer coaching programs?</h3>
                            <p class="text-gray-600">
                                Yes! We connect players with experienced coaches and offer structured 
                                development programs for players at all skill levels.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Success Modal -->
    <div id="successModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-2xl p-8 max-w-md mx-4 text-center">
            <div class="bg-green-100 text-green-600 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-check text-2xl"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Thank You!</h3>
            <p class="text-gray-600 mb-6">
                Thank you for your feedback! We will reach out to you soon.
            </p>
            <button 
                onclick="closeModal()" 
                class="bg-cue-green text-white px-6 py-3 rounded-lg font-semibold hover:bg-cue-green-light transition duration-300"
            >
                Close
            </button>
        </div>
    </div>

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

    <!-- Scripts -->
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            const logo = document.getElementById('nav-logo');
            const navLinks = document.querySelectorAll('#navbar a[id^="nav-link"]');
            const mobileMenuButton = document.getElementById('mobile-menu-btn');
            const mobileMenu = document.getElementById('mobile-menu');
            
            if (mobileMenuButton && mobileMenu) {
                mobileMenuButton.addEventListener('click', function() {
                    mobileMenu.classList.toggle('hidden');
                });
            }
            
            if (window.scrollY > 50) {
                // Scrolled state - white background, dark text
                navbar.className = 'bg-white shadow-lg fixed w-full z-50 transition-all duration-300';
                logo.className = 'text-2xl font-bold text-cue-green transition-colors duration-300';
                navLinks.forEach(link => {
                    link.className = 'text-gray-700 hover:text-cue-green transition duration-300';
                });
                blogBtn.className = 'bg-cue-green text-white px-4 py-2 rounded-lg hover:bg-cue-green-light transition duration-300';
                if (mobileMenuButton) {
                    mobileMenuButton.className = 'text-gray-700 hover:text-cue-green';
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

        // Contact form submission
        document.getElementById('contactForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const submitText = document.getElementById('submitText');
            const submitSpinner = document.getElementById('submitSpinner');
            
            // Show loading state
            submitBtn.disabled = true;
            submitText.textContent = 'Sending...';
            submitSpinner.classList.remove('hidden');
            
            // Get form data
            const formData = new FormData(this);
            const data = {
                name: formData.get('name'),
                email: formData.get('email'),
                subject: formData.get('subject'),
                message: formData.get('message')
            };
            
            try {
                const response = await fetch('/contact', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Show success modal
                    document.getElementById('successModal').classList.remove('hidden');
                    // Reset form
                    this.reset();
                } else {
                    alert('There was an error sending your message. Please try again.');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('There was an error sending your message. Please try again.');
            } finally {
                // Reset button state
                submitBtn.disabled = false;
                submitText.textContent = 'Send Message';
                submitSpinner.classList.add('hidden');
            }
        });

        // Close modal function
        function closeModal() {
            document.getElementById('successModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('successModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
