<?php

use Livewire\Component;
use App\Models\ATJ\Clients;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
new class extends Component
{
    //
    public $headers=[
        ['key' => 'id', 'label' => '#', 'class' => 'w-1/12 hidden'],
        ['key' => 'code', 'label' => 'Code', 'class' => 'w-1/12'],
        ['key' => 'name', 'label' => 'Name', 'class' => 'w-4/12'],
        ['key' => 'uname', 'label' => 'User name', 'class' => 'w-4/12'],
        ['key' => 'email', 'label' => 'Email', 'class' => 'w-4/12'],
        ['key' => 'expirity', 'label' => 'Expirity', 'class' => 'w-4/12'],
        ['key' => 'remain', 'label' => 'Remain', 'class' => 'w-4/12'],
        ['key' => 'action', 'label' => 'Action', 'class' => 'w-1/12'],
    ];


    #[On('refreshAdminClientPage')]
    public function render(){
        $clients = Clients::paginate(10);
        return $this->view(['clients' => $clients]);
    }

    public function delete($clientId){
        Clients::find($clientId)->delete();
    }

    public function login($clientId){
        $user = User::find(Clients::find($clientId)->user_id);

        Auth::login($user);
        $this->redirectRoute('client');
    }
};
?>

<div>

    <x-card title="Admin | Client Management" shadow separator>
        <livewire:pages::addClient.create />
        <livewire:pages::addClient.edit />
        <br/>
        <hr/>
        <x-table :headers="$headers" :rows="$clients" with-pagination>
            @scope('cell_uname', $clients)
                {{$clients->user->name}}
            @endscope
            @scope('cell_email', $clients)
                {{$clients->user->email}}
            @endscope
            @scope('cell_action', $clients)
                <x-button label="Edit" class="btn-warning" icon="o-pencil" wire:click="$dispatch('clientEditModalVisible', { clientId: {{ $clients->id }} })" />
                <x-button label="Delete" class="btn-danger" icon="o-trash" wire:click="delete({{ $clients->id }})" />
                <x-button label="Login As" class="btn-danger" icon="o-user-circle" wire:click="login({{ $clients->id }})" />
            @endscope
        </x-table>
    </x-card>
    {{-- Simplicity is the consequence of refined emotions. - Jean D'Alembert --}}
</div>
