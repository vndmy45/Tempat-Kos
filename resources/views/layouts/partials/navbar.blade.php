<!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-transparent position-fixed w-100">
        <div class="container">
          <a class="navbar-brand" href="{{ url('/') }}">
            <img src="{{ asset('assets/img/logo.png') }}" alt="" width="30">
            RumahKost
          </a>
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
          <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
              <li class="nav-item mx-2">
                <a class="nav-link {{ Request::is('/') ? 'active fw-bold' : '' }}" href="{{ url('/') }}">BERANDA</a>
              </li>
              <li class="nav-item mx-2">
                <a class="nav-link {{ Request::is('pencarian') ? 'active fw-bold' : '' }}" href="{{ url('/pencarian') }}">PENCARIAN</a>
              </li>
            </ul>
        <div class="ms-auto">

          @if (Auth::check())
            <a href="" class="button-primary">{{ Auth::user()->name }}</a>
            <a href="{{ route('logout') }}" class="button-secundary" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Keluar</a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
            
                
            @else
                
             <a href="{{ route('register') }}" class="button-primary">Daftar</a>
            <a href="{{ route('login') }}" class="button-secundary">Masuk</a>

          @endif
           
        </div>

          </div>
        </div>
      </nav>