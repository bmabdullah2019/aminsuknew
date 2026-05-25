<!-- Featured Section -->
<section class="py-2 py-md-4" style="background: linear-gradient(to bottom, #FAF4B3, #ECC7CF);">
    <div class="container my-2 my-md-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <h2 class="text-center p-2 p-md-4 rounded" style="background-color:#FBEFF7;border:2px dashed #F1ACE7">
                    আমাদের থেকে বিস্তারিত জানতে এই নাম্বারে কল করুন {{ $contact->phone }}
                </h2>
                <div class="row justify-content-center my-2 my-md-4 gy-2">
                    <div class="col-md-6 custom_btn">
                        <div class="shadow-lg">
                            <a href="tel:{{ $contact->phone }}" 
                               class="btn btn-danger btn-lg d-block py-md-3 fs-2 fw-bolder button-3d button-animated-border">
                                <i class="fa-solid fa-phone"></i> আমাদের কল করুন
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="shadow-lg">
                            <a href="https://wa.me/{{ $contact->whatsapp }}" 
                               class="btn btn-success btn-lg d-block py-md-3 fs-2 text-light fw-bolder button-3d button-animated-border">
                                <i class="fa-brands fa-whatsapp"></i> হোয়াটসঅ্যাপ  
                            </a>
                        </div>
                    </div>
                </div>
                <h2 class="text-center p-2 p-md-4 rounded" style="background-color:#FBEFF7;border:2px dashed #F1ACE7">
                    {!! $campaign->heading_4 !!}
                </h2>
            </div>
        </div>
    </div>
</section>
