@extends('layouts.app')

@section('content')
<div class="card-body">
  <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">
      Trashed Items
    </h1>

    <div class="btn-toolbar mb-2 mb-md-0">
      <div class="btn-group">
        <a href="{{ route('create-item') }}" class="btn btn-sm btn-outline-secondary">
            New Item
        </a>
        <a href="{{ route('index-item') }}" class="btn btn-sm btn-outline-secondary">
            Items List
        </a>
      </div>
    </div>
  </div>

  @if($items->isEmpty())
    <p class="lead text-muted">
      Trash is empty, no trashed item.
    </p>

    <a href="{{ route('index-item') }}" class="btn btn-primary">
      Go back to Items list 
    </a>       
  @else  
    <div class="table-responsive">
      <table class="table table-striped table-sm">
        <thead>
          <tr>
            <th>Thumbnail</th>

            <th>Title</th>
            
            <th>Display Title</th>

            <th>Category</th>

            <th class="text-center">Actions</th>
          </tr>
        </thead>

        <tbody>
          @foreach($items as $item)
            <tr>
              <td class="align-middle">
                <img src="{{ $item->thumbnail ? (env('AWS_URL') . '/' . $item->thumbnail) : '/img/demo.jpg' }}" height="50" alt="{{ $item->title }}">
              </td>

              <td class="align-middle">
                {{$item->title}}
              </td>

              <td class="align-middle">
                {{$item->display_title}}
              </td>
              
              <td class="align-middle">
                {{$item->category->name}}
              </td>
              
              <td class="text-center align-middle">
                <form action="{{ route('destroy-trash-item', $item->id) }}" method="POST">
                  {{ csrf_field() }}
                  {{ method_field('DELETE')}}

                    <div class="btn-group">
                      <a href="{{ route('restore-item', $item->id) }}" class="btn btn-sm btn-outline-secondary">
                        Restore
                      </a>

                      <button type="submit" class="btn btn-sm btn-outline-secondary">Permanent Delete</button>
                    </div>
                </form>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>

        {{ $items->links() }}
    </div>
  @endif
</div>
@endsection