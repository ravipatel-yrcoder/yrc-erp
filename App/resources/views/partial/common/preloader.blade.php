<style>
#pageLoading {
    position: fixed;
    inset: 0;
    background: rgba(30,33,41,0.25);
    display: none;
    z-index: 99999;
}
</style>
<div id="pageLoading" class="justify-content-center align-items-center flex-column">
    <div class="spinner-border spinner-border-md text-primary" role="status"></div>
    <div class="pt-5">Loading...</div>
</div>
@push('scripts')
<script>
function showPageLoading() {
    const loaderDiv = document.getElementById("pageLoading");
    loaderDiv.style.display = "flex";
}

function hidePageLoading() {
    const loaderDiv = document.getElementById("pageLoading");
    loaderDiv.style.display = "none";
}
</script>
@endpush