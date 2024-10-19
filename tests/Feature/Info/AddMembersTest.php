<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Livewire\Chat\Chat;
use Namu\WireChat\Livewire\Info\AddMembers;
use Namu\WireChat\Models\Attachment;
use Namu\WireChat\Models\Conversation;
use Workbench\App\Models\User;

test('user must be authenticated', function () {

    $conversation= Conversation::factory()->create();
    Livewire::test(AddMembers::class,['conversation'=>$conversation])
        ->assertStatus(401);
});


test('aborts if user doest not belog to conversation', function () {


    $auth = User::factory()->create(['id' => '345678']);


    $conversation= Conversation::factory()->create();
    Livewire::actingAs($auth)->test(AddMembers::class,['conversation'=>$conversation])
                 ->assertStatus(403);
});


test('aborts if conversation is private', function () {


    $auth = User::factory()->create(['id' => '345678']);
    $receiver = User::factory()->create();


    $conversation= $auth->createConversationWith($receiver);
    Livewire::actingAs($auth)->test(AddMembers::class,['conversation'=>$conversation])
                 ->assertStatus(403,'Cannot add members to private conversation');
});




test('authenticaed user can access component ', function () {
    $auth = User::factory()->create(['id' => '345678']);

    $conversation = $auth->createGroup('My Group');

    Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation])
        ->assertStatus(200);
});


describe('presence test',function(){


    test('Add Members title is set', function () {

        Config::set('wirechat.max_group_members',1000);

        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        $request =  Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation]);

        //* since converstaion already have one user which is the auth then default is 1
        $request
                ->assertSee('Add Members')
                ->assertSee('1 / 1000');

    });


    test('Create button is set and method wired', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        $request =  Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation]);

        $request
                ->assertSee('Save')
                ->assertMethodWired('save') ;
    });



});


describe('actions test',function(){


    test('Search can be filtered', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        #add participant
        $conversation->addParticipant(User::factory()->create(['name'=>'Micheal']));


        $request =  Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation]);
        $request
                ->set('search','Mic')
                ->assertSee('Micheal');
      });


     test('toggleMember() method words correclty', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        #add participant
        $user = User::factory()->create(['name'=>'Micheal']);
        $conversation->addParticipant($user);
        $request =  Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation]);
  
        $request
                #attempt to add member
                ->call('toggleMember',$user->id,$user->getMorphClass())
                ->assertDontSee('Micheal');
     });


     test('it updated number when new members are added or removed', function () {

        Config::set('wirechat.max_group_members',1000);
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        #add participant
        $user = User::factory()->create(['name'=>'Micheal']);

        $request =  Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation]);
  
        $request
                #attempt to add member
                ->call('toggleMember',$user->id,$user->getMorphClass())
                ->assertSee('2 / 1000')
                #attempt to remove member
                ->call('toggleMember',$user->id,$user->getMorphClass())
                ->assertSee('1 / 1000');
     });


     test('existing member cannot be added to selectedMembers', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        #add participant
        $user = User::factory()->create(['name'=>'Micheal']);

        $request =  Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation]);
  
        $request
                #first add member
                ->call('toggleMember',$user->id,$user->getMorphClass())
                ->assertSee('Micheal')
                #then remove memener
                ->call('toggleMember',$user->id,$user->getMorphClass())
                ->assertDontSee('Micheal');
     });


     test('it shows "Already added to group" if already added to group', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        #add participant
        $conversation->addParticipant(User::factory()->create(['name'=>'John']));

        $request =  Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation]);
  
        $request
                #first add member
                ->set('search','John')
                #user  
                ->assertSee('Already added to group');
     });




     test('it saved new members to database ', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        #add participant
        $user = User::factory()->create(['name'=>'Micheal']);
        $request =  Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation]);
  
        $request
                #attempt to add member
                ->call('toggleMember',$user->id,$user->getMorphClass())
                ->call('save');

        $exists = $conversation->participants()->where('participantable_id',$user->id)->exists();
        expect($exists)->toBe(true);

     });


     test('it dispatches refresh event after saving ', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        #add participant
        $user = User::factory()->create(['name'=>'Micheal']);
        $request =  Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation]);
  
        $request
                #attempt to add member
                ->call('toggleMember',$user->id,$user->getMorphClass())
                ->call('save');

        $request->assertDispatched('refresh');

     });


     test('it dispatches closeModal event after saving ', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        #add participant
        $user = User::factory()->create(['name'=>'Micheal']);
        $request =  Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation]);
  
        $request
                #attempt to add member
                ->call('toggleMember',$user->id,$user->getMorphClass())
                ->call('save');

        $request->assertDispatched('closeModal');

     });




});