@php
    $alert = null;
    if ($errors->any()) {
        $alert = ['type' => 'error', 'title' => 'Revisa los datos', 'text' => implode("\n", $errors->all())];
    } elseif (session('warning')) {
        $alert = ['type' => 'warning', 'title' => 'Atención', 'text' => session('warning')];
    } elseif (session('account_exists')) {
        $alert = ['type' => 'info', 'title' => 'Cuenta registrada', 'text' => session('account_exists')];
    } elseif (session('status')) {
        $alert = ['type' => 'success', 'title' => 'Correcto', 'text' => session('status')];
    }
@endphp
@if ($alert)
<script>
    if (typeof Swal !== 'undefined') {
        Swal.fire(@json($alert));
    }
</script>
@endif
