<?php

use Livewire\Component;
use Livewire\Attributes\On;
use Mary\Traits\Toast;
use App\Models\ATJ\Clients;

new class extends Component
{
    //
    public $clientEditModalVisble = false;
    public $code;
    public $name;
    public $expirity;
    public $clientId;
    use Toast;

    #[On('clientEditModalVisible')]
    public function clientEditModalVisible($clientId){
        $this->clientId = $clientId;
        $client = Clients::find($clientId);
        //dd($client);
        $this->code = $client->code;
        $this->name = $client->name;
        $this->expirity = $client->expirity;
        $this->clientEditModalVisble = true;
    }
    public function updateClient(){
        $this->validate([
            'code' => 'required|string',
            'name' => 'required|string',
            'expirity' => 'required|date',
        ]);

        Clients::find($this->clientId)->update([
            'code' => $this->code,
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
    <x-modal wire:model="clientEditModalVisble" title="Edit Client" class="backdrop-blur">
        <div class="text-left">
            <div class="flex flex-wrap -mx-3">
                <div class="w-full max-w-full px-3 mb-6 sm:w-6/12 sm:flex-none xl:mb-0 xl:w-6/12">
                    <x-input wire:model="code" label="Code" />
                </div>
                <div class="w-full max-w-full px-3 mb-6 sm:w-6/12 sm:flex-none xl:mb-0 xl:w-6/12">
                    <x-input wire:model="name" label="Name of client" />
                </div>
            </div>
            <div class="flex flex-wrap -mx-3">
                <div class="w-full max-w-full px-3 mb-6 sm:w-6/12 sm:flex-none xl:mb-0 xl:w-6/12">
                    <x-datetime label="My date" wire:model="expirity" />
                </div>
            </div>
            <br/>
            <div class="flex flex-wrap -mx-3">
                <div class="w-full max-w-full px-3 mb-6 sm:w-12/12 sm:flex-none xl:mb-0 xl:w-12/12">
                    <x-button wire:click="updateClient" label="Update" class="btn-success"/>
                    <x-button wire:click="enableCreateClient" label="Cancel" class="btn-warning"/>
                </div>
            </div>
        </div>
    </x-modal>
    {{-- When there is no desire, all things are at peace. - Laozi --}}
</div>
