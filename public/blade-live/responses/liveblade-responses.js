
const LiveBladeResponse = {
    
    redirectOrFreshPage: function(url) {
        // reload the next page
        window.location.href = url;
    },


    reloadOrRedirect: function(response) {
        if (response.redirect === '/dashboard') {
            
            return true;
        }

        if (response.success && response.reload && !response.refresh) {
            this.displaySuccessMessage(response.message);
            this.fetchAndReloadComponent(response)
            return true;
        } else if(response.success && !response.reload && response.refresh) {
            this.redirectOrFreshPage(response.redirect);
            return true;
        } else if (response.success && !response.reload && !response.refresh) {
            this.displaySuccessMessage(response.message || 'Operation Successful!');
            return true;
        } else {
            this.displayErrorMessage(response.message || 'Operation Failed');
            return false
        }

    },

    fetchAndReloadComponent: function(response) {
        if (response.componentId) {
            const componentContainer = document.getElementById(response.componentId);
            if (componentContainer) {
                // switch cases for the differenty bladefiles to reload their newly added content
                const url = `${response.redirect}?bladeFileToReload=${response.componentId}`;
                fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value // Add CSRF token if needed
                    },
                    credentials: 'same-origin', // Ensure CSRF token works in some browsers
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(html => {
                    // Replace the component
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    // Locate the new content within the fetched HTML using the component ID
                    const newContent = doc.getElementById(response.componentId);

                    if (newContent) {
                        // Replace the existing component content
                        componentContainer.innerHTML = newContent.innerHTML;
                        console.log('Component content replaced successfully.');
                        if (typeof initializeComponentScripts === 'function') {
                            // If the function exists, call it
                            initializeComponentScripts();
                        } else {
                            console.warn("initializeComponentScripts function is not defined.");
                        }
                        
                     } else {
                        console.log('An Error Occured');
                    }


                })
                .catch(error => {
                    console.error('Error fetching new content:', error);
                    this.displayErrorMessage('Component Not Found.');
                });

            } else {
                this.displayErrorMessage('Component Not Found');
            }

            document.addEventListener('DOMContentLoaded', function() {
                initializeComponentScripts(); // Initialize the component scripts when the page loads
            });
        }
    },


    
    displayErrorMessage: function(message) {
        const swalMessage = ''
        if (message){
            swalMessage = message;
        } else {
            swalMessage = 'Operations Failed';
        }
        Swal.fire({
            toast: true,
            position: 'top-end',  // Places the alert at the top-right corner
            icon: 'error',        // Error icon
            title: `<span style="color: red;">${swalMessage}</span>`,       // The message to display
            showConfirmButton: false,
            timer: 5000,          // Auto close after 5 seconds
            timerProgressBar: true, // Show a progress bar
            customClass: {
                popup: 'swal2-show', // Adds a fade-in effect for the popup
            }
        });

        const statusDiv = document.getElementById('status');
        statusDiv.innerHTML = `<div class="alert alert-danger">${message}</div>`;
        
        // Optionally, remove the message after a few seconds
        setTimeout(() => {
            statusDiv.innerHTML = '';
        }, 5000);
    },
    

    displaySuccessMessage: function(message) {
        let swalMessage = 'Operation Successful!';  // Default message
    
        if (message != null) {
            swalMessage = message;
        }
        
        Swal.fire({
            toast: true,
            position: 'top-end',  // Places the alert at the top-right corner
            icon: 'success',      // Success icon
            title: `<span style="color: green;">${swalMessage}</span>`,       // The message to display
            showConfirmButton: false,
            timer: 5000,          // Auto close after 5 seconds
            timerProgressBar: true, // Show a progress bar
            customClass: {
                popup: 'swal2-show', // Adds a fade-in effect for the popup
            }
        });

        // Incase swal fire is not called
        const statusDiv = document.getElementById('status');
        if (statusDiv) {
            statusDiv.innerHTML = `<div class="alert alert-success">${swalMessage}</div>`;
        }
        
        // Optionally, remove the message after a few seconds
        setTimeout(() => {
            statusDiv.innerHTML = '';
        }, 5000); 
    },
    

    displayValidationErrors: function(errors) {
        // Clear previous error messages
        const existingErrors = document.getElementsByClassName('text-danger');
        Array.from(existingErrors).forEach(error => error.remove());
    
        // Loop through each error field and display the error
        for (const field in errors) {
            if (errors.hasOwnProperty(field)) {
                // Use getElementById to directly target the input field by its id
                const inputElement = document.getElementById(field);
                // console.log(inputElement)
                if (inputElement) {
                    const errorMessage = errors[field][0]; // Get the first error message
    
                    // Create a new span element to display the error message
                    const errorSpan = document.createElement('span');
                    errorSpan.classList.add('text-danger');
                    errorSpan.textContent = errorMessage;
    
                    // Append the error span after the input element
                    inputElement.parentNode.insertBefore(errorSpan, inputElement.nextSibling);
                    this.displayValidationMessage();
                }
            }
        }
    },    

    displayValidationErrorsForInstances: function(errors, userId) {
        // Clear previous error messages
        const existingErrors = document.getElementsByClassName('text-danger');
        Array.from(existingErrors).forEach(error => error.remove());
    
        // Loop through each error field and display the error
        for (const field in errors) {
            if (errors.hasOwnProperty(field)) {
                // Construct the input field's ID by appending the userId
                const inputElement = document.getElementById(field + userId); // e.g., 'edit_name1', 'edit_email1'
    
                if (inputElement) {
                    const errorMessage = errors[field][0]; // Get the first error message
    
                    // Create a new span element to display the error message
                    const errorSpan = document.createElement('span');
                    errorSpan.classList.add('text-danger');
                    errorSpan.textContent = errorMessage;
    
                    // Append the error span after the input element
                    inputElement.parentNode.insertBefore(errorSpan, inputElement.nextSibling);
                    this.displayValidationMessage();
                }
            }
        }
    },
    

    displayErrorMessage: function(message) {
        // Use SweetAlert2 to display a general error message with larger size
        Swal.fire({
            icon: 'error',
            title: 'Error',
            html: `<div style="max-height: 500px; overflow-y: auto;">${message}</div>`, // Increase the max-height to make it more scrollable
            confirmButtonText: 'OK',
            showCloseButton: false,
            allowOutsideClick: false,
            width: '90%',
        });
    },

    displayValidationMessage: function() {
        const swalMessage = 'Validation Errors!'
        Swal.fire({
            toast: true,
            position: 'top-end',  // Places the alert at the top-right corner
            icon: 'error',        // Error icon
            title: `<span style="color: red;">${swalMessage}</span>`,       // The message to display
            showConfirmButton: false,
            timer: 5000,          // Auto close after 5 seconds
            timerProgressBar: true, // Show a progress bar
            customClass: {
                popup: 'swal2-show', // Adds a fade-in effect for the popup
            }
        });
    },
    
    
};
export default LiveBladeResponse;
