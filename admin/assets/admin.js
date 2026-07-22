(function(){
  const sidebar = document.getElementById('adminSidebar');
  const backdrop = document.getElementById('sidebarBackdrop');
  const openBtns = document.querySelectorAll('[data-sidebar-toggle]');

  function toggle(){
    sidebar.classList.toggle('open');
    backdrop.classList.toggle('show');
  }
  function close(){
    sidebar.classList.remove('open');
    backdrop.classList.remove('show');
  }

  openBtns.forEach(function(btn){ btn.addEventListener('click', toggle); });
  if(backdrop) backdrop.addEventListener('click', close);
})();
