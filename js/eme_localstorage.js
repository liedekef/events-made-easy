document.addEventListener('DOMContentLoaded', function() {
    const rememberMeCheckbox = document.querySelector("form.eme-rememberme input#eme_rememberme");
    if (rememberMeCheckbox) {
        let eme_rememberme_checked = localStorage.getItem('eme_rememberme');
        if (eme_rememberme_checked == 1) {
            rememberMeCheckbox.checked = true;
        }
    }
    
    // Helper function to set field value from localStorage if field exists and is empty
    function setFieldFromStorage(selector, storageKey) {
        const field = document.querySelector(`form.eme-rememberme input[name=${selector}]`);
        if (field && field.value === '') {
            field.value = localStorage.getItem(storageKey) || '';
        }
    }
    
    // Helper function to set field value from localStorage (for task fields)
    function setTaskFieldFromStorage(selector, storageKey) {
        const field = document.querySelector(`form.eme-rememberme input[name=${selector}]`);
        if (field) {
            field.value = localStorage.getItem(storageKey) || '';
        }
    }
    
    // Set form fields from localStorage
    setFieldFromStorage('lastname', 'eme_lastname');
    setFieldFromStorage('firstname', 'eme_firstname');
    setFieldFromStorage('email', 'eme_email');
    setFieldFromStorage('phone', 'eme_phone');
    
    // Set task fields from localStorage
    setTaskFieldFromStorage('task_lastname', 'eme_lastname');
    setTaskFieldFromStorage('task_firstname', 'eme_firstname');
    setTaskFieldFromStorage('task_email', 'eme_email');
    setTaskFieldFromStorage('task_phone', 'eme_phone');
    
    // Handle form submission
    const rememberMeForms = document.querySelectorAll('form.eme-rememberme');
    rememberMeForms.forEach(form => {
        form.addEventListener('submit', function() {
            const rememberCheckbox = this.querySelector('input#eme_rememberme');
            
            if (rememberCheckbox && rememberCheckbox.checked) {
                localStorage.setItem('eme_rememberme', 1);
                
                // Helper function to save field to localStorage
                function saveFieldToStorage(selector, storageKey) {
                    const field = form.querySelector(`input[name=${selector}]`);
                    if (field) {
                        localStorage.setItem(storageKey, field.value);
                    }
                }
                
                // Save regular fields
                saveFieldToStorage('lastname', 'eme_lastname');
                saveFieldToStorage('firstname', 'eme_firstname');
                saveFieldToStorage('email', 'eme_email');
                saveFieldToStorage('phone', 'eme_phone');
                
                // Save task fields
                saveFieldToStorage('task_lastname', 'eme_lastname');
                saveFieldToStorage('task_firstname', 'eme_firstname');
                saveFieldToStorage('task_email', 'eme_email');
                saveFieldToStorage('task_phone', 'eme_phone');
            } else {
                // Remove from localStorage if not checked
                localStorage.removeItem('eme_lastname');
                localStorage.removeItem('eme_firstname');
                localStorage.removeItem('eme_email');
                localStorage.removeItem('eme_phone');
                localStorage.removeItem('eme_rememberme');
            }
        });
    });
});
