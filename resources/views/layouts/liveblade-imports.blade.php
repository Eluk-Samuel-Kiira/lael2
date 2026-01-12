<!-- important imports for laravel liveblades -->
<script src="{{ asset('blade-live/forms/forms.min.js') }}" type="module"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


<style>
    body {
        font-family: Arial, sans-serif;
    }
    nav {
        margin-bottom: 10px;
    }
    a {
        margin-right: 10px;
        text-decoration: none;
        color: blue;
    }
    a:hover {
        text-decoration: underline;
    }
    #content {
        padding: 20px;
        border: 1px solid #ddd;
        margin-top: 10px;
        min-height: 100px;
        position: relative;
    }
    #loader {
        display: none;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 40px;
        height: 40px;
        border: 4px solid rgba(0, 0, 0, 0.1);
        border-top: 4px solid blue;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        z-index: 10;
    }
    @keyframes spin {
        from {
            transform: rotate(0deg);
        }
        to {
            transform: rotate(360deg);
        }
    }
</style>

<script>
    // Function to show the loader
    function showLoader() {
        const loader = document.getElementById('loader');
        if (loader) {
            loader.style.display = 'block';
        }
    }

    // Function to hide the loader
    function hideLoader() {
        const loader = document.getElementById('loader');
        if (loader) {
            loader.style.display = 'none';
        }
    }


    // Function to handle navigation
    function navigateToGuestPage(url) {
        showLoader(); // Show loader during content loading

        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(data => {
                history.pushState({ url: url }, null, url); // Store state in history

                // Extract title and update page content
                const titleMatch = data.match(/<title>(.*?)<\/title>/i);
                document.title = titleMatch ? titleMatch[1] : 'Default Title';
                document.getElementById('kt_app_root').innerHTML = data;

                setTimeout(hideLoader, 300);
            })
            .catch(error => {
                console.error('Error fetching content:', error);
                document.getElementById('kt_app_root').innerHTML = '404 Page Not Found.';
                hideLoader();
            });
    }


    // Handle back/forward navigation
    window.addEventListener('popstate', (event) => {
        if (event.state && event.state.url) {
            renderGuestPage(event.state.url); // Load the correct content
        } else {
            renderGuestPage(window.location.pathname); // Default behavior
        }
    });


    // Function to load content based on URL
    function renderGuestPage(url) {
        const pageContent = document.getElementById('kt_app_root');

        if (!pageContent) {
            // console.error('Error: Element #kt_app_root not found.');
            return; // Stop execution if the element is missing
        }

        showLoader(); // Show loader during content loading

        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text(); // Get the HTML content
            })
            .then(data => {
                // Extract the title from the fetched content
                const titleMatch = data.match(/<title>(.*?)<\/title>/i);
                document.title = titleMatch ? titleMatch[1] : 'Default Title'; // Update the document title

                // Insert the fetched content into the page
                pageContent.innerHTML = data;

                // Hide the loader after a small delay (optional)
                setTimeout(hideLoader, 300);
            })
            .catch(error => {
                console.error('Error fetching content:', error);
                
                if (pageContent) {
                    pageContent.innerHTML = '404 Page Not Found.'; // Fallback content
                }

                hideLoader();
            });
    }


    // Reload apge especiall when going to the database
    function reloadTo(url) {
        window.location.href = url; // Redirect on success
    }

    // Initial load
    renderGuestPage(window.location.pathname);
</script>


<!-- Guest Js Defind functions  -->
@include('layouts.guest-js')

<!-- App/Dashboard Js Defind functions  -->
@include('layouts.app-js')
@include('layouts.procurement-js')
@include('layouts.pos-js')


<script>
    // Main Initiator Functions
    
    function handleFormSubmiter(formData, submitButtonId) {
        
        // console.log(formData);

        // This is the loader
        LiveBlade.toggleButtonLoading(submitButtonId, true);
        
        LiveBlade.submitFormItems(formData)
        .then(noErrors => {
            console.log(noErrors);
            
            // Only for Login 
            if (formData["routeName"] === "/login") {

                const alertOptions = noErrors
                    ? {
                        icon: 'success',
                        title: 'Success!',
                        text: 'You have successfully logged in!',
                        confirmButtonText: 'Ok, got it!',
                        backdrop: true
                    }
                    : {
                        icon: 'error',
                        title: 'Oops!',
                        text: 'Account Suspended, Contact Admin to re-establish it or These credentials do not match our records.',
                        confirmButtonText: 'Ok, got it!',
                        backdrop: true
                    };
                
                Swal.fire(alertOptions).then(() => {
                    if (noErrors) {
                        // window.location.href = '/dashboard'; // Redirect on success
                        reloadTo('/dashboard');
                    }
                });
                
            }

        })
        .catch(error => {
            console.error('An unexpected error occurred:', error);
        })
        .finally(() => {
            LiveBlade.toggleButtonLoading(submitButtonId, false);
        });

    }

    
</script>



