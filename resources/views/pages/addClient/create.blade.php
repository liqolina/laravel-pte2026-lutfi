<?php

use Livewire\Component;
use App\Models\ATJ\Clients;
use Mary\Traits\Toast;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
new class extends Component
{
    //
    public $createClientEnable = false;
    public $code;
    public $name;
    public $expirity;
    use Toast;

    public function enableCreateClient(){
        if($this->createClientEnable == false) {
            $this->createClientEnable = true;
        }else{
            $this->createClientEnable = false;
        }
    }
    public function saveClient(){
        $this->validate([
            'code' => 'required|string',
            'name' => 'required|string',
            'expirity' => 'required|date',
        ]);

        $user = User::firstOrCreate([
            'name' =>  $this->code,
            'email' =>  $this->code."@bems.id",
            'password' => Hash::make($this->code."12345##"),
        ]);


        Clients::firstOrCreate([
            'code' => $this->code,
            'user_id' => $user->id,
            'name' => $this->name,
            'expirity' => $this->expirity,
        ]);
        $this->success(
            'New client has been svaed',
            timeout: 1000,
            position: 'toast-top toast-center'
        );
        $this->code = null;
        $this->name = null;
        $this->expirity = null;

        $this->dispatch('refreshAdminClientPage');
    }
};
?>

<div>

    @if($createClientEnable === false)
        <x-button wire:click="enableCreateClient" label="Create" class="btn-primary btn-dash"/>

    @else
        <x-card title="Add Client" shadow separator class="bg-blue-100">
            <div class="text-left">
                <div class="flex flex-wrap -mx-3">
                    <div class="w-full max-w-full px-3 mb-6 sm:w-3/12 sm:flex-none xl:mb-0 xl:w-3/12">
                        <x-input wire:model="code" label="Code" />
                    </div>
                    <div class="w-full max-w-full px-3 mb-6 sm:w-4/12 sm:flex-none xl:mb-0 xl:w-4/12">
                        <x-input wire:model="name" label="Name of client" />
                    </div>
                </div>
                <div class="flex flex-wrap -mx-3">
                    <div class="w-full max-w-full px-3 mb-6 sm:w-4/12 sm:flex-none xl:mb-0 xl:w-4/12">
                        <x-datetime label="My date" wire:model="expirity" />
                    </div>
                </div>
                <br/>
                <div class="flex flex-wrap -mx-3">
                    <div class="w-full max-w-full px-3 mb-6 sm:w-4/12 sm:flex-none xl:mb-0 xl:w-4/12">
                        <x-button wire:click="saveClient" label="Save" class="btn-success"/>
                        <x-button wire:click="enableCreateClient" label="Cancel" class="btn-warning"/>
                    </div>
                </div>
            </div>
        </x-card>
    @endif
    {{-- It always seems impossible until it is done. - Nelson Mandela --}}
</div>
