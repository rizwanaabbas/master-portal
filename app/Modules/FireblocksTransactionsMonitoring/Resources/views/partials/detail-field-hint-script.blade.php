<style>
    .tooltip.fb-detail-field-tooltip .tooltip-inner {
        max-width: min(360px, 90vw);
        text-align: left;
    }
    .fb-detail-field-hint {
        font-size: 0.85rem;
        vertical-align: middle;
        text-decoration: none !important;
    }
    .fb-detail-field-hint:hover,
    .fb-detail-field-hint:focus {
        color: #886ab5 !important;
        outline: none;
    }
</style>
<script>
    (function () {
        var tooltipTemplate =
            '<div class="tooltip fb-detail-field-tooltip" role="tooltip">' +
            '<div class="arrow"></div><div class="tooltip-inner"></div></div>';

        window.fbInitMonitoringDetailTooltips = function (root) {
            var $scope = root ? $(root) : $(document);
            $scope.find('.fb-detail-field-hint[data-toggle="tooltip"]').each(function () {
                var $el = $(this);
                if ($el.data('bs.tooltip')) {
                    $el.tooltip('dispose');
                }
                $el.tooltip({
                    container: 'body',
                    boundary: 'window',
                    trigger: 'hover focus',
                    template: tooltipTemplate,
                });
            });
        };

        $(function () {
            fbInitMonitoringDetailTooltips('.fb-monitoring-detail');
            fbInitMonitoringDetailTooltips('#fb-monitoring-dashboard');
        });

        $(document).on('shown.bs.modal', '#tx-detail-modal', function () {
            fbInitMonitoringDetailTooltips('#tx-detail-body');
        });
    })();
</script>
