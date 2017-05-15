<!-- REQUIRED JS SCRIPTS -->

<!-- JQuery and bootstrap are required by Laravel 5.3 in resources/assets/js/bootstrap.js-->
<!-- Laravel App -->
<script src="{{ asset('/js/app.js') }}" type="text/javascript"></script>
<!-- Optionally, you can add Slimscroll and FastClick plugins.
      Both of these plugins are recommended to enhance the
      user experience. Slimscroll is required when using the
      fixed layout. -->
<script>
window.Laravel = <?php echo json_encode([
        'csrfToken' => csrf_token(),
]); ?>
</script>
<script type="text/javascript" src="{{ asset("vue/js/manifest.js") }}"></script>
<script type="text/javascript" src="{{ asset("vue/js/vendor.js") }}"></script>
<script type="text/javascript" src="{{ asset("vue/js/app.js") }}"></script>