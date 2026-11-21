<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\ParentArea;
use App\Http\Controllers\Super;
use App\Http\Controllers\User;
use Illuminate\Support\Facades\Route;

// ---------- Commun (C1–C6) ----------
Route::get('/', [CommonController::class, 'welcome'])->name('welcome');
Route::get('/menu-public', [CommonController::class, 'publicMenu'])->name('menu.public');

Route::middleware('guest')->group(function () {
    Route::get('/inscription', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/inscription', [AuthController::class, 'register']);
    Route::get('/connexion', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/connexion', [AuthController::class, 'login'])->middleware('throttle:10,1');
});
Route::post('/deconnexion', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/profil', [CommonController::class, 'profile'])->name('profile');
    Route::put('/profil', [CommonController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profil/mot-de-passe', [CommonController::class, 'updatePassword'])->name('profile.password');
    Route::post('/profil/photo', [CommonController::class, 'uploadPhoto'])->name('profile.photo');
    Route::delete('/profil/photo', [CommonController::class, 'deletePhoto'])->name('profile.photo.delete');
    Route::post('/profil/langue', [CommonController::class, 'setLocale'])->name('profile.locale');
    Route::get('/notifications', [CommonController::class, 'notifications'])->name('notifications');
    Route::get('/notifications/{notification}', [CommonController::class, 'openNotification'])->name('notifications.open');
    Route::post('/notifications/tout-lire', [CommonController::class, 'readAll'])->name('notifications.readAll');
    Route::get('/mot-de-passe/nouveau', [CommonController::class, 'showChangePassword'])->name('password.change');
    Route::put('/mot-de-passe/nouveau', [CommonController::class, 'changePassword'])->middleware('throttle:6,1')->name('password.change.update');
    Route::get('/confirmer-mot-de-passe', [CommonController::class, 'showConfirm'])->name('password.confirm');
    Route::post('/confirmer-mot-de-passe', [CommonController::class, 'confirm'])->middleware('throttle:6,1');
});

// ---------- Utilisateur : élève ou personnel (U1–U13) ----------
Route::middleware(['auth', 'role:user'])->group(function () {
    Route::get('/accueil', [User\HomeController::class, 'index'])->name('user.home');
    Route::post('/accueil/classe', [User\HomeController::class, 'confirmClass'])->name('user.class');
    Route::get('/menu', [User\MenuController::class, 'index'])->name('menu');
    Route::get('/plats/{plat}', [User\MenuController::class, 'dish'])->name('dish.show');
    Route::get('/reservations', [User\ReservationController::class, 'index'])->name('reservations.index');
    Route::get('/reservations/nouvelle/{menu}', [User\ReservationController::class, 'create'])->name('reservations.create');
    Route::post('/reservations/nouvelle/{menu}', [User\ReservationController::class, 'store'])->name('reservations.store');
    Route::get('/reservations/{reservation}', [User\ReservationController::class, 'show'])->name('reservations.show');
    Route::get('/reservations/{reservation}/modifier', [User\ReservationController::class, 'edit'])->name('reservations.edit');
    Route::put('/reservations/{reservation}', [User\ReservationController::class, 'update'])->name('reservations.update');
    Route::delete('/reservations/{reservation}', [User\ReservationController::class, 'destroy'])->name('reservations.destroy');
    Route::get('/portefeuille', [User\WalletController::class, 'index'])->name('wallet');
    Route::get('/portefeuille/recharger', [User\WalletController::class, 'topupForm'])->name('wallet.topup');
    Route::post('/portefeuille/recharger', [User\WalletController::class, 'topup']);
    Route::get('/portefeuille/recu/{transaction}', [User\WalletController::class, 'receipt'])->name('wallet.receipt');
    Route::get('/avis/{menu}', [User\ReviewController::class, 'create'])->name('reviews.create');
    Route::post('/avis/{menu}', [User\ReviewController::class, 'store'])->name('reviews.store');
    Route::get('/profil/qr-parent', [User\ProfileController::class, 'parentQr'])->name('profile.qr');
    Route::post('/profil/qr-parent', [User\ProfileController::class, 'newParentQr'])->name('profile.qr.new');
    Route::get('/profil/suppression', [User\ProfileController::class, 'deletion'])->name('profile.deletion');
    Route::post('/profil/suppression', [User\ProfileController::class, 'requestDeletion']);
    Route::delete('/profil/suppression', [User\ProfileController::class, 'cancelDeletion'])->name('profile.deletion.cancel');
});

// ---------- Parent (P1–P6) ----------
Route::middleware(['auth', 'role:parent'])->prefix('parent')->name('parent.')->group(function () {
    Route::get('/', [ParentArea\ParentController::class, 'home'])->name('home');
    Route::get('/enfants', [ParentArea\ParentController::class, 'children'])->name('children');
    Route::get('/lier', [ParentArea\ParentController::class, 'linkForm'])->name('link');
    // Limite d'essais : empêche de deviner un code de secours par essais successifs
    Route::post('/lier/verifier', [ParentArea\ParentController::class, 'linkFind'])->middleware('throttle:5,1')->name('link.find');
    Route::post('/lier', [ParentArea\ParentController::class, 'link'])->middleware('throttle:5,1')->name('link.store');
    Route::get('/enfants/{enfant}', [ParentArea\ParentController::class, 'child'])->name('child');
    Route::put('/enfants/{enfant}', [ParentArea\ParentController::class, 'updateChild'])->name('child.update');
    Route::post('/enfants/{enfant}/reserver', [ParentArea\ParentController::class, 'book'])->name('child.book');
    Route::get('/recharger', [ParentArea\ParentController::class, 'topupChoose'])->name('topup.choose');
    Route::get('/enfants/{enfant}/recharger', [ParentArea\ParentController::class, 'topupForm'])->name('topup');
    Route::post('/enfants/{enfant}/recharger', [ParentArea\ParentController::class, 'topup']);
    Route::get('/paiements', [ParentArea\ParentController::class, 'payments'])->name('payments');
    Route::get('/alertes', [ParentArea\ParentController::class, 'alerts'])->name('alerts');
    Route::put('/alertes', [ParentArea\ParentController::class, 'saveAlerts'])->name('alerts.save');
});

// ---------- Comptoir (A11) : agents de comptoir, administrateurs et super administrateurs ----------
Route::middleware(['auth', 'role:counter,admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/comptoir', [Admin\CounterController::class, 'index'])->name('counter');
    Route::post('/comptoir', [Admin\CounterController::class, 'scan'])->middleware('throttle:60,1')->name('counter.scan');
});

// ---------- Administration (A1–A12) ----------
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/menus', [Admin\MenuController::class, 'index'])->name('menus');
    Route::post('/menus/{menu}/plats', [Admin\MenuController::class, 'addDish'])->name('menus.add');
    Route::put('/menus/items/{item}', [Admin\MenuController::class, 'updateItem'])->name('menus.item');
    Route::delete('/menus/items/{item}', [Admin\MenuController::class, 'removeDish'])->name('menus.remove');
    Route::post('/menus/dupliquer', [Admin\MenuController::class, 'duplicate'])->name('menus.duplicate');
    Route::post('/menus/publier', [Admin\MenuController::class, 'publish'])->name('menus.publish');
    Route::get('/commandes', [Admin\OrderController::class, 'index'])->name('orders');
    Route::get('/commandes/export', [Admin\OrderController::class, 'export'])->name('orders.export');
    Route::get('/commandes/production', [Admin\OrderController::class, 'production'])->name('orders.production');
    Route::post('/commandes/{reservation}/servir', [Admin\OrderController::class, 'serve'])->name('orders.serve');
    Route::post('/commandes/cloturer', [Admin\OrderController::class, 'close'])->name('orders.close');
    Route::post('/commandes/anti-gaspi', [Admin\OrderController::class, 'antigaspi'])->name('orders.antigaspi');
    Route::get('/alertes', [Admin\AlertController::class, 'index'])->name('alerts');
    Route::post('/alertes/{reservation}/verifier', [Admin\AlertController::class, 'check'])->name('alerts.check');
    Route::post('/alertes/{reservation}/contacter', [Admin\AlertController::class, 'contact'])->name('alerts.contact');
    Route::post('/alertes/tout-lire', [Admin\AlertController::class, 'readAll'])->name('alerts.readAll');
    Route::get('/plats', [Admin\DishController::class, 'index'])->name('dishes');
    Route::get('/plats/nouveau', [Admin\DishController::class, 'create'])->name('dishes.create');
    Route::post('/plats', [Admin\DishController::class, 'store'])->name('dishes.store');
    Route::get('/plats/{dish}', [Admin\DishController::class, 'edit'])->name('dishes.edit');
    Route::put('/plats/{dish}', [Admin\DishController::class, 'update'])->name('dishes.update');
    Route::post('/plats/{dish}/photo', [Admin\DishController::class, 'photo'])->name('dishes.photo');
    Route::delete('/plats/{dish}/photo', [Admin\DishController::class, 'deletePhoto'])->name('dishes.photo.delete');
    Route::post('/plats/{dish}/archiver', [Admin\DishController::class, 'archive'])->name('dishes.archive');
    Route::delete('/plats/{dish}', [Admin\DishController::class, 'destroy'])->name('dishes.destroy');
    Route::get('/ingredients', [Admin\StockController::class, 'index'])->name('stocks');
    Route::post('/ingredients', [Admin\StockController::class, 'store'])->name('stocks.store');
    Route::post('/ingredients/{ingredient}/mouvement', [Admin\StockController::class, 'move'])->name('stocks.move');
    Route::get('/ingredients/bon-de-commande', [Admin\StockController::class, 'order'])->name('stocks.order');
    Route::get('/avis', [Admin\ReviewController::class, 'index'])->name('reviews');
    Route::post('/avis/{review}', [Admin\ReviewController::class, 'moderate'])->name('reviews.moderate');
    Route::put('/avis/mots-interdits', [Admin\ReviewController::class, 'words'])->name('reviews.words');
    Route::get('/utilisateurs', [Admin\UserController::class, 'index'])->name('users');
    Route::get('/utilisateurs/{user}', [Admin\UserController::class, 'show'])->name('users.show');
    Route::post('/utilisateurs/{user}/solde', [Admin\UserController::class, 'adjust'])->name('users.adjust');
    Route::get('/statistiques/classes', [Admin\ReportController::class, 'classes'])->name('classes');
    Route::get('/rapports', [Admin\ReportController::class, 'index'])->name('reports');
    Route::get('/rapports/export', [Admin\ReportController::class, 'export'])->name('reports.export');
    Route::get('/parametres', [Admin\SettingsController::class, 'index'])->name('settings');
    Route::put('/parametres', [Admin\SettingsController::class, 'update'])->name('settings.update');
    Route::post('/parametres/periode', [Admin\SettingsController::class, 'newPeriod'])->name('settings.period');
});

// ---------- Super administration (S1–S6) : 403 pour un administrateur ----------
Route::middleware(['auth', 'role:super_admin'])->prefix('super')->name('super.')->group(function () {
    Route::get('/admins', [Super\AdminController::class, 'index'])->name('admins');
    Route::post('/admins', [Super\AdminController::class, 'store'])->name('admins.store');
    Route::put('/admins/{user}', [Super\AdminController::class, 'update'])->name('admins.update');
    Route::post('/admins/{user}/statut', [Super\AdminController::class, 'toggle'])->name('admins.toggle');
    Route::post('/admins/{user}/mot-de-passe', [Super\AdminController::class, 'resetPassword'])->name('admins.password');
    Route::get('/comptes', [Super\AccountController::class, 'index'])->name('accounts');
    Route::post('/comptes/{user}/corbeille', [Super\AccountController::class, 'trash'])->name('accounts.trash');
    Route::get('/corbeille', [Super\AccountController::class, 'trashIndex'])->name('trash');
    Route::post('/corbeille/{user}/restaurer', [Super\AccountController::class, 'restore'])->name('trash.restore');
    Route::delete('/corbeille/{user}', [Super\AccountController::class, 'destroy'])->name('trash.destroy');
    Route::post('/corbeille/purge', [Super\AccountController::class, 'purge'])->name('trash.purge');
    Route::put('/corbeille/delai', [Super\AccountController::class, 'delay'])->name('trash.delay');
    Route::get('/demandes', [Super\AccountController::class, 'requests'])->name('requests');
    Route::post('/demandes/{deletionRequest}', [Super\AccountController::class, 'handleRequest'])->name('requests.handle');
    Route::middleware('password.confirm')->group(function () {
        Route::get('/paiements', [Super\PaymentController::class, 'index'])->name('payments');
        Route::put('/paiements/{method}/cles', [Super\PaymentController::class, 'keys'])->name('payments.keys');
        Route::delete('/paiements/{method}/cles', [Super\PaymentController::class, 'deleteKeys'])->name('payments.keys.delete');
        Route::post('/paiements/{method}/tester', [Super\PaymentController::class, 'test'])->name('payments.test');
        Route::post('/paiements/{method}/activer', [Super\PaymentController::class, 'toggle'])->name('payments.toggle');
    });
    Route::get('/parametres', [Super\SettingsController::class, 'index'])->name('settings');
    Route::put('/parametres', [Super\SettingsController::class, 'update'])->name('settings.update');
    Route::put('/parametres/smtp', [Super\SettingsController::class, 'smtp'])->name('settings.smtp');
    Route::post('/parametres/smtp/test', [Super\SettingsController::class, 'smtpTest'])->middleware('throttle:5,1')->name('settings.smtp.test');
    Route::get('/api', [Super\ApiKeyController::class, 'index'])->name('api');
    Route::post('/api', [Super\ApiKeyController::class, 'store'])->name('api.store');
    Route::put('/api/{apiKey}', [Super\ApiKeyController::class, 'update'])->name('api.update');
    Route::post('/api/{apiKey}/statut', [Super\ApiKeyController::class, 'toggle'])->name('api.toggle');
    Route::post('/api/{apiKey}/renouveler', [Super\ApiKeyController::class, 'rotate'])->name('api.rotate');
    Route::post('/api/{apiKey}/revoquer', [Super\ApiKeyController::class, 'revoke'])->name('api.revoke');
    Route::delete('/api/{apiKey}', [Super\ApiKeyController::class, 'destroy'])->name('api.destroy');
    Route::get('/audit', [Super\AuditController::class, 'index'])->name('audit');
    Route::get('/audit/export', [Super\AuditController::class, 'export'])->name('audit.export');
});
