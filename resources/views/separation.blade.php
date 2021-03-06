@extends('mainTemplate')

@section('javascript')
<script src="js/groupsAD.js" ></script >
<script src="js/separation.js" ></script >
<script src="js/menu.js" ></script >
@endsection

@section('css')
<link rel="stylesheet" href="css/navigation.css" >
<link rel="stylesheet" href="css/theForms.css" >
@endsection


@section('content')

<p class="subHeader">Employee Separation Section</p>
<br>
<form method="post" action="/separation_add" name="separation" id="separation" >
    <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>" >
    <label >Search user</label >
    <input type="text" id="email" name="email" class="inputRender" > <span
        id="searchProgress" ></span >
    <br ><br >

    <div class="report" id="report" ></div >


    <div id="homeMenu" >
        <br ><br ><br >

        <p class="subHeader" >Other options: <br ></p >
        <ul class="navigation" style="text-align: center" >
            <li class="myNavigation navigationLink" id="home" >Home Screen</li >
        </ul >

    </div >


</form >
@endsection
