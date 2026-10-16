@if (!request()->routeIs(['home']))
    @php
        $breadcrumbContent = getContent('breadcrumb.content', true);
    @endphp

    <!-- 
    <div class="mt-100 text-white bg-black">
        <div class="container">
            <div class="inner-banner-wrapper">
                <h2 class="title pt-40 pb-50">{{ __($pageTitle) }}</h2>
            </div>
        </div>
    </div> 
    -->

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2  border-bottom">
        <h2 class="display-5 h2 mx-auto my-3 text-center" style="color: #135D26;">{!! __($pageTitle)  !!}</h2>
        <div class="btn-toolbar mb-2 mb-md-0">
          <!-- <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-success">Notification</button>
          </div> -->
        </div>
    </div>
<header>

   <!--  <div class="pricing-header p-3 pb-md-4 mx-auto text-center">
      <h1 class="display-4 fw-normal">Pricing</h1>
      <p class="fs-5 text-muted">Quickly build an effective pricing table for your potential customers with this Bootstrap example. It’s built with default Bootstrap components and utilities with little customization.</p>
    </div>
</header>

<div class="position-relative overflow-hidden p-3 p-md-5 m-md-3 text-center bg-light">
    <div class="col-md-5 p-lg-5 mx-auto my-5">
      <h1 class="display-4 fw-normal">Punny headline</h1>
      <p class="lead fw-normal">And an even wittier subheading to boot. Jumpstart your marketing efforts with this example based on Apple’s marketing pages.</p>
      <a class="btn btn-outline-secondary" href="#">Coming soon</a>
    </div>
    <div class="product-device shadow-sm d-none d-md-block"></div>
    <div class="product-device product-device-2 shadow-sm d-none d-md-block"></div>
  </div> -->

@endif

