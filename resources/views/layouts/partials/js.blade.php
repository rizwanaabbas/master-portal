<script src="{{ asset("assets/js/vendors.bundle.js") }}"></script>
<script src="{{ asset("assets/js/app.bundle.js") }}"></script>
<script type="text/javascript">
    /* Activate smart panels */
    $('#js-page-content').smartPanel();

</script>
<!-- The order of scripts is irrelevant. Please check out the plugin pages for more details about these plugins below: -->
<script src="{{ asset("assets/js/statistics/chartjs/chartjs.bundle.js") }}"></script>
<script src="{{ asset("assets/js/miscellaneous/jqvmap/jqvmap.bundle.js") }}"></script>
<script src="{{ asset("assets/js/notifications/sweetalert2/sweetalert2.bundle.js") }}"></script>

<script src="{{ asset("assets/js/formplugins/select2/select2.bundle.js") }}"></script>

<script src="{{ asset("assets/js/dependency/moment/moment.js") }}"></script>
<script src="{{ asset("assets/js/miscellaneous/fullcalendar/fullcalendar.bundle.js") }}"></script>
<script src="{{ asset("assets/js/formplugins/bootstrap-datepicker/bootstrap-datepicker.js") }}"></script>
<script src="{{ asset("assets/js/datagrid/datatables/datatables.bundle.js") }}"></script>
<script src="{{ asset("assets/js/datagrid/datatables/datatables.export.js") }}"></script>

<script src="{{ asset('assets/js/jquery.simplegauge.js') }}"></script>


<script>

    $(document).ready(function() {
        $('.select2').select2();
    })

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    function deleteNotification(id){

        $.post('/notification/delete', {nid: id }, function(response, status){

            $("#notification-"+id).remove();
        })
    }

    function deleteAllNotifications(){

        $.post('/notification/delete/all', function(response, status){

            removeElementsByClass('notify')
           // $("#notification-"+id).remove();
        })
    }

    function removeElementsByClass(className){
        const elements = document.getElementsByClassName(className);
        while(elements.length > 0){
            elements[0].parentNode.removeChild(elements[0]);
        }
    }

</script>
