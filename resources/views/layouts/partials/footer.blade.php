<footer class="page-footer" role="contentinfo">
    <div class="d-flex align-items-center flex-1 text-muted">
        <span class="hidden-md-down fw-700">{{ date("Y") }} © {{ $ownCompany ? $ownCompany->short_name : '' }}</span>
    </div>
</footer>
<script>
    setTimeout(function() {
        jQuery(window).on("beforeunload", function(){
            $('#loading-spinner').show();  
        });  
    },500);
    
</script>