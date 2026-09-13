@php
$containerFooter =
isset($configData['contentLayout']) && $configData['contentLayout'] === 'compact'
? 'container-xxl'
: 'container-fluid';
@endphp

<!-- Footer-->
<footer class="content-footer footer bg-footer-theme">
    <div class="{{ $containerFooter }}">
        <div class="footer-container d-flex align-items-center justify-content-center py-4">
            <div class="text-body">
                &#169;
                <script>
                document.write(new Date().getFullYear());
                </script>
                Code Ninja Pro. All rights reserved.
            </div>
        </div>
    </div>
</footer>
<!-- / Footer -->
