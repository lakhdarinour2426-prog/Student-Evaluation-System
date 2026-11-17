document.addEventListener('DOMContentLoaded', function () {

    // Smooth navigation
    document.querySelectorAll('.sidebar a[href^="#"]').forEach(link => {
        link.addEventListener('click', function () {
            document.querySelectorAll('.sidebar a').forEach(item => {
                item.classList.remove('active');
            });

            this.classList.add('active');
        });
    });

    // Confirmation before deleting records
    document.querySelectorAll('form[onsubmit]').forEach(form => {
        form.addEventListener('submit', function (event) {
            if (!confirm('Are you sure you want to delete this record?')) {
                event.preventDefault();
            }
        });
    });

});