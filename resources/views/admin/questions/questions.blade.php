@extends('admin.layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content-wrapper">
            <div class="row">
                <div class="col-lg-12 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">{{ $isVendor ? 'Customer questions' : 'Product questions' }}</h4>
                            <p class="card-description">Answers appear on the product page for every shopper. Unanswered questions are listed first.</p>
                            @include('admin.layout.alerts')
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th style="width: 22%">Product</th>
                                            <th>Question</th>
                                            <th style="width: 38%">Answer</th>
                                            @unless ($isVendor)
                                                <th>Visible</th>
                                            @endunless
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($questions as $question)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('products.show', $question->product_id) }}#questions" target="_blank" rel="noopener">{{ $question->product?->product_name }}</a>
                                                </td>
                                                <td style="white-space: normal">
                                                    {{ $question->question }}
                                                    <br><small class="text-muted">{{ $question->user?->name }} · {{ $question->created_at?->diffForHumans() }}</small>
                                                </td>
                                                <td style="white-space: normal">
                                                    <form method="post" action="{{ url('admin/questions/'.$question->id.'/answer') }}">
                                                        @csrf
                                                        <textarea name="answer" class="form-control mb-2" rows="2" maxlength="1000" required placeholder="Write an answer">{{ $question->answer }}</textarea>
                                                        <button type="submit" class="btn btn-sm {{ $question->isAnswered() ? 'btn-outline-primary' : 'btn-primary' }}">{{ $question->isAnswered() ? 'Update answer' : 'Publish answer' }}</button>
                                                        @if ($question->answered_at)
                                                            <small class="text-muted ml-2">Answered {{ $question->answered_at->diffForHumans() }}</small>
                                                        @endif
                                                    </form>
                                                </td>
                                                @unless ($isVendor)
                                                    <td>
                                                        <form method="post" action="{{ url('admin/questions/'.$question->id.'/status') }}">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm {{ $question->status === 1 ? 'btn-outline-secondary' : 'btn-outline-success' }}">{{ $question->status === 1 ? 'Hide' : 'Show' }}</button>
                                                        </form>
                                                    </td>
                                                @endunless
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="text-center text-muted">No questions yet.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('admin.layout.footer')
    </div>
@endsection
