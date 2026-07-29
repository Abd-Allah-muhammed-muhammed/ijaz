<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow text-center">
                <div class="card-header bg-danger text-white">
                    <h4><i class="fas fa-times-circle me-2"></i>Payment Failed</h4>
                </div>
{{--                <div class="card-body">--}}
{{--                    <p class="fs-5 mb-4">Unfortunately, your payment could not be processed.</p>--}}
{{--                    <a href="/payment/mock" class="btn btn-warning">--}}
{{--                        <i class="fas fa-redo me-2"></i>Try Again--}}
{{--                    </a>--}}
{{--                </div>--}}
            </div>
        </div>
    </div>
</div>
<x-payment::debug-panel :payment="$payment" />
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded',function () {
    window.opener.postMessage('payment-failed','{{url('/')}}');
  })
</script>
</body>
</html>
