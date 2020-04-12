<script type="text/javascript">
    function showToast(){
        var shortCutFunction = 'error';
        var msg = '<?= h($message) ?>';
        var title = 'Error';

        toastr.options = {
          "closeButton": true,
          "debug": false,
          "newestOnTop": false,
          "progressBar": true,
          "rtl": false,
          "positionClass": "toast-top-right",
          "preventDuplicates": false,
          "onclick": null,
          "showDuration": 300,
          "hideDuration": 1000,
          "timeOut": 5000,
          "extendedTimeOut": 1000,
          "showEasing": "swing",
          "hideEasing": "linear",
          "showMethod": "fadeIn",
          "hideMethod": "fadeOut"
        }

        var $toast = toastr[shortCutFunction](msg, title);
        
        if(typeof $toast === 'undefined'){
            return;
        }
    };

    showToast();
</script>
