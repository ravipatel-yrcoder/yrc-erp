<?php $__env->startSection('title', 'Welcome'); ?>
<?php $__env->startSection('bodyClasses', 'antialiased bg-gradient-to-br from-white to-slate-100 dark:from-slate-950 dark:to-slate-900 min-h-screen'); ?>

<?php $__env->startSection('content'); ?>
<div class="h-100">
<h2 class="text-center mt-5">User Edit</h2>

<form name="user_form">
    First name<br/>
    <input type="text" name="firstName" value="{$user->firstName}"/><br/>

    Last name<br/>
    <input type="text" name="lastName" value="{$user->lastName}"/><br/>

    Email<br/>
    <input type="text" name="email" value="{$user->email}"/><br/>

    Phone<br/>
    <input type="text" name="phone" value="{$user->phone}"/><br/>

    <br/>
    <button type="button" name="save">Save</button>
<form>

</div>
<script>
jQuery(document).ready(function(){
    
    let userId="{/literal}{$user->id}{literal}";

    jQuery("button[name='save']").click(function(){

        var formData = new FormData(jQuery("form[name='user_form']")[0]);
        jQuery.ajax({
            url: `/users/update/id/${userId}`,
            method: 'POST',
            processData: false,
            contentType: false,
            data: formData,
            success: function(response) {
                console.log('Data saved:', response);
            },
            error: function(xhr, status, error) {
                console.error('Error saving data:', error);
            }
        });
    });
});
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.default', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\Code Base\Frameworks\TinyPHP\App\resources\views\default\users/index.blade.php ENDPATH**/ ?>