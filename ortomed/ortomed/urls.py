
from django.contrib import admin
from django.urls import path

from cotizador.views import *
urlpatterns = [
    path('admin/', admin.site.urls),
    path('', login),
    

    path("inventario/list/", InventarioListView.as_view(), name="inventario_list"),
    path("inventario/add/", InventarioCreateview.as_view(), name="inventario_add"),


]

