@php $sel = old('scopes', $k?->scopes ?? ['menus:read']); @endphp
<div class="field"><label>Nom de l’application ou du service</label><input class="input" name="name" value="{{ old('name', $k?->name) }}" placeholder="Ex. Application mobile, ERP, borne de comptoir" required>@error('name')<span class="error">{{ $message }}</span>@enderror</div>
<div class="cat-label" style="margin-top:4px">Permissions</div>
<div class="stack" style="max-height:260px;overflow:auto">
    @foreach (\App\Models\ApiKey::SCOPES as $scope => [$label, $desc])
        <label class="check" style="align-items:flex-start"><input type="checkbox" name="scopes[]" value="{{ $scope }}" {{ in_array($scope, $sel, true) ? 'checked' : '' }} style="margin-top:3px">
            <span><b>{{ $label }}</b> <code class="xs">{{ $scope }}</code>@if (str_ends_with($scope, ':write'))<span class="badge orange xs">écriture</span>@endif<br><span class="xs muted">{{ $desc }}</span></span></label>
    @endforeach
</div>
@error('scopes')<span class="error">{{ $message }}</span>@enderror
<div class="grid-2-eq mt">
    <div class="field"><label>Limite (requêtes / minute)</label><input class="input" type="number" name="rate_limit" min="1" max="1000" value="{{ old('rate_limit', $k?->rate_limit ?? 60) }}" required></div>
    <div class="field"><label>Expiration (facultatif)</label><input class="input" type="date" name="expires_at" value="{{ old('expires_at', $k?->expires_at?->toDateString()) }}" min="{{ today()->addDay()->toDateString() }}">@error('expires_at')<span class="error">{{ $message }}</span>@enderror</div>
</div>
<div class="field"><label>Notes (facultatif)</label><input class="input" name="notes" value="{{ old('notes', $k?->notes) }}" placeholder="Contact technique, usage…"></div>
