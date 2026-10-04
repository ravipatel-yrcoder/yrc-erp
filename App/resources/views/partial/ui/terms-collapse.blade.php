{{-- $prefix: ID prefix, e.g. 'so', 'po', 'pi'. Generates: {prefix}TermsWrap / {prefix}TermsContent / {prefix}Terms --}}
<div class="col-12" id="{{ $prefix }}TermsWrap">
    <div class="d-flex align-items-center gap-1 mt-2" role="button"
         data-bs-toggle="collapse" data-bs-target="#{{ $prefix }}TermsContent" aria-expanded="false">
        <div class="detail-label mb-0">Terms &amp; Conditions</div>
        <i class="bx bx-chevron-down fs-5 text-secondary"></i>
    </div>
    <div class="collapse" id="{{ $prefix }}TermsContent">
        <div class="small mt-1" id="{{ $prefix }}Terms"></div>
    </div>
</div>
