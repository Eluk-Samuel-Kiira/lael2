
<script>
    //Defind your functions
    

    function passwordResetEmailAction() {
        const form = document.getElementById('forgot_password_form');
        const submitButton = document.getElementById('passwordResetButton');

        if (!form || !submitButton) {
            console.error('form or submit button not found.');
            return;
        }

        // Capture form data
        const formData = Object.fromEntries(new FormData(form));

        // Append route and method
        formData.routeName = '/forgot-password';
        formData._method = 'POST';

        // Call form submission handler
        handleFormSubmiter(formData, submitButton);
    }

    function passwordResetAction() {
        const form = document.getElementById('reset_password_form');
        const submitButton = document.getElementById('resetPasswordButton');

        if (!form || !submitButton) {
            console.error('form or submit button not found.');
            return;
        }

        // Capture form data
        const formData = Object.fromEntries(new FormData(form));

        // Append route and method
        formData.routeName = '/reset-password';
        formData._method = 'POST';

        // Call form submission handler
        handleFormSubmiter(formData, submitButton);
    }

    function loginAction() {
        const form = document.getElementById('kt_sign_in_form');
        const submitButton = document.getElementById('loginSubmitButton');

        if (!form || !submitButton) {
            console.error('Login form or submit button not found.');
            return;
        }

        // Capture form data
        const formData = Object.fromEntries(new FormData(form));

        // Append route and method
        formData.routeName = '/login';
        formData._method = 'POST';

        // Call form submission handler
        handleFormSubmiter(formData, submitButton);
    }
</script>