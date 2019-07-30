$(function() {
    $('.confirm').on('click', function(e) {

        if (!confirm('Are you sure you want to delete this record?')) {
            e.preventDefault();
            return false;
        }
    });
});
