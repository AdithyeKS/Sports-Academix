const wrapper = document.querySelector('.wrapper');
const loginLink = document.querySelector('.login-link');
const registerLink = document.querySelector('.register-link');
const btnPopup = document.querySelector('.btnLogin-popup');
const iconClose = document.querySelector('.icon-close');

// --- Original Animation Logic ---

registerLink.addEventListener('click', () => {
    wrapper.classList.add('active');
});

loginLink.addEventListener('click', () => {
    wrapper.classList.remove('active');
});

btnPopup.addEventListener('click', () => {
    wrapper.classList.add('active-popup');
});

iconClose.addEventListener('click', () => {
    wrapper.classList.remove('active-popup');
    // Clear errors when closing the form
    clearAllErrors();
});

// --- Validation Logic ---

const loginForm = document.querySelector('.form-box.login form');
const registerForm = document.querySelector('.form-box.register form');

// Function to display an error message
const setError = (inputElement, message) => {
    const inputBox = inputElement.parentElement;
    // Remove existing error before adding a new one
    clearError(inputElement);
    
    inputBox.classList.add('error');
    const errorDisplay = document.createElement('div');
    errorDisplay.className = 'error-message';
    errorDisplay.innerText = message;
    inputBox.appendChild(errorDisplay);
}

// Function to clear an error message
const clearError = (inputElement) => {
    const inputBox = inputElement.parentElement;
    if (inputBox.classList.contains('error')) {
        inputBox.classList.remove('error');
        const errorDisplay = inputBox.querySelector('.error-message');
        if (errorDisplay) {
            inputBox.removeChild(errorDisplay);
        }
    }
}

// Function to clear all errors on a form
const clearAllErrors = () => {
    const errorInputs = document.querySelectorAll('.input-box.error');
    errorInputs.forEach(inputBox => {
        const input = inputBox.querySelector('input');
        clearError(input);
    });
}

// Function to validate email format
const isValidEmail = email => {
    const re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
}

// Function to validate password complexity
const validatePassword = (password) => {
    const errors = [];
    if (password.length < 8) {
        errors.push("Min.8 characters.");
    }
    if (!/[A-Z]/.test(password)) {
        errors.push("Use uppercase.");
    }
    if (!/[a-z]/.test(password)) {
        errors.push("Use lowercase.");
    }
    if (!/[0-9]/.test(password)) {
        errors.push("Must contain a number.");
    }
    return errors.join(' ');
}

// --- Event Listener for Login Form Submission ---
loginForm.addEventListener('submit', e => {
    e.preventDefault(); // Prevent page refresh
    clearAllErrors();

    const emailInput = loginForm.querySelector('input[type="text"]');
    const passwordInput = loginForm.querySelector('input[type="password"]');
    let isValid = true;

    // Validate email
    if (emailInput.value.trim() === '') {
        setError(emailInput, 'Email is required.');
        isValid = false;
    } else if (!isValidEmail(emailInput.value.trim())) {
        setError(emailInput, 'Please enter a valid email address.');
        isValid = false;
    }

    // Validate password

    const passwordValidationError = validatePassword(passwordInput.value);
    if (passwordValidationError) {
        setError(passwordInput, passwordValidationError);
        isValid = false;
   /* if (passwordInput.value.trim() === '') {
        setError(passwordInput, 'Password is required.');
        isValid = false;*/
    }

    if (isValid) {
        console.log('Login form is valid. Submitting...');
        // Here you would typically submit the form to a server
        // loginForm.submit(); 
    }
});

// --- Event Listener for Registration Form Submission ---
registerForm.addEventListener('submit', e => {
    e.preventDefault(); // Prevent page refresh
    clearAllErrors();

    const usernameInput = registerForm.querySelector('input[type="text"]');
    const emailInput = registerForm.querySelectorAll('input[type="text"]')[1];
    const passwordInput = registerForm.querySelector('input[type="password"]');
    const termsCheckbox = registerForm.querySelector('input[type="checkbox"]');
    let isValid = true;

    // Validate Username
    if (usernameInput.value.trim() === '') {
        setError(usernameInput, 'Username is required.');
        isValid = false;
    }
    
    // Validate Email
    if (emailInput.value.trim() === '') {
        setError(emailInput, 'Email is required.');
        isValid = false;
    } else if (!isValidEmail(emailInput.value.trim())) {
        setError(emailInput, 'Please enter a valid email address.');
        isValid = false;
    }

    // Validate Password
    const passwordValidationError = validatePassword(passwordInput.value);
    if (passwordValidationError) {
        setError(passwordInput, passwordValidationError);
        isValid = false;
    }
    
    // Validate Terms
    if (!termsCheckbox.checked) {
        // You can create an alert or custom message for the checkbox
        alert('You must agree to the terms and conditions.');
        isValid = false;
    }

    if (isValid) {
        console.log('Registration form is valid. Submitting...');
        // Here you would typically submit the form to a server
        // registerForm.submit();
    }
});

// Add event listeners to clear errors as the user types
document.querySelectorAll('.input-box input').forEach(input => {
    input.addEventListener('input', () => {
        clearError(input);
    });
});