@props(['action', 'shape' => 'circle', 'label' => 'Choisir une photo', 'id' => 'up'.uniqid(), 'camera' => true])
{{-- Composant d'upload commun : galerie ou caméra, JPG/PNG/WebP, 5 Mo max, recadrage (Cropper.js), aperçu, envoi en WebP --}}
<form method="POST" action="{{ $action }}" enctype="multipart/form-data" id="{{ $id }}-form" class="inline">
    @csrf
    <input type="file" name="photo" id="{{ $id }}-file" accept="image/jpeg,image/png,image/webp" class="hidden">
    <input type="file" id="{{ $id }}-cam" accept="image/jpeg,image/png,image/webp" capture="environment" class="hidden">
    <div class="row wrap">
        <button type="button" class="btn sm ghost" onclick="document.getElementById('{{ $id }}-file').click()">{!! ic('image') !!} {{ __('Galerie') }}</button>
        @if ($camera)<button type="button" class="btn sm ghost" onclick="document.getElementById('{{ $id }}-cam').click()">{!! ic('camera') !!} {{ __('Appareil photo') }}</button>@endif
    </div>
</form>
@push('modals')
<div class="modal-bg hidden" id="{{ $id }}-modal" data-locked="1">
    <div class="modal">
        <h2>{{ __('Recadrer la photo') }}</h2>
        <div style="max-height:320px;overflow:hidden;background:#111;border-radius:12px"><img id="{{ $id }}-img" alt="" style="max-width:100%"></div>
        <div class="row mt"><span class="small muted grow" id="{{ $id }}-info"></span>
            <div class="chips"><button type="button" class="chip {{ $shape === 'circle' ? 'active' : '' }}" data-shape="circle">{{ __('Cercle') }}</button><button type="button" class="chip {{ $shape !== 'circle' ? 'active' : '' }}" data-shape="square">{{ __('Carré') }}</button></div>
        </div>
        <div class="actions"><button type="button" class="btn ghost" data-close>{{ __('Annuler') }}</button><button type="button" class="btn" id="{{ $id }}-save">{{ __('Enregistrer') }}</button></div>
    </div>
</div>
@endpush
@once
@push('head')<link rel="stylesheet" href="{{ asset('vendor/cropper.min.css') }}"><style>.round-crop .cropper-view-box,.round-crop .cropper-face{border-radius:50%}</style>@endpush
@push('scripts')<script src="{{ asset('vendor/cropper.min.js') }}"></script>
<script>
window.CVUpload = function (id) {
  const MAX = {{ (int) config('cantine.upload_max_kb') }} * 1024, TYPES = ['image/jpeg', 'image/png', 'image/webp'];
  const form = document.getElementById(id + '-form'), file = document.getElementById(id + '-file'), cam = document.getElementById(id + '-cam');
  const modal = document.getElementById(id + '-modal'), img = document.getElementById(id + '-img');
  let cropper = null, original = null;
  const pick = (input) => {
    const f = input.files[0]; if (!f) return;
    if (!TYPES.includes(f.type)) { App.toast('Format non accepté (JPG, PNG ou WebP uniquement)', 'red'); input.value = ''; return; }
    if (f.size > MAX) { App.toast('Image trop lourde (5 Mo maximum)', 'red'); input.value = ''; return; }
    original = f;
    img.src = URL.createObjectURL(f);
    modal.classList.remove('hidden');
    img.onload = () => {
      document.getElementById(id + '-info').textContent = (f.size / 1024 / 1024).toFixed(1).replace('.', ',') + ' Mo · ' + img.naturalWidth + '×' + img.naturalHeight;
      if (cropper) cropper.destroy();
      cropper = new Cropper(img, { aspectRatio: 1, viewMode: 1, dragMode: 'move', autoCropArea: 1 });
    };
  };
  file.addEventListener('change', () => pick(file));
  cam.addEventListener('change', () => pick(cam));
  modal.querySelectorAll('[data-shape]').forEach((b) => b.addEventListener('click', () => {
    modal.querySelectorAll('[data-shape]').forEach((x) => x.classList.toggle('active', x === b));
    modal.classList.toggle('round-crop', b.dataset.shape === 'circle');
  }));
  modal.classList.toggle('round-crop', {{ $shape === 'circle' ? 'true' : 'false' }});
  document.getElementById(id + '-save').addEventListener('click', () => {
    cropper.getCroppedCanvas({ width: 640, height: 640 }).toBlob((blob) => {
      const dt = new DataTransfer();
      dt.items.add(new File([blob], 'photo.webp', { type: 'image/webp' }));
      file.files = dt.files;
      form.submit();
    }, 'image/webp', 0.85);
  });
};
</script>@endpush
@endonce
@push('scripts')<script>CVUpload('{{ $id }}');</script>@endpush
