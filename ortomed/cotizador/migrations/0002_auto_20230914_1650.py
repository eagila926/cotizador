# Generated by Django 3.2.20 on 2023-09-14 21:50

from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('cotizador', '0001_initial'),
    ]

    operations = [
        migrations.CreateModel(
            name='ActivosFormulas',
            fields=[
                ('id', models.BigAutoField(auto_created=True, primary_key=True, serialize=False, verbose_name='ID')),
                ('codigoOdoo', models.IntegerField()),
                ('activo', models.CharField(max_length=100)),
                ('cantidad', models.DecimalField(decimal_places=2, max_digits=10)),
                ('unidad', models.CharField(default='g', max_length=10)),
            ],
            options={
                'verbose_name': 'Activo Formula',
                'verbose_name_plural': 'Activos Formulas',
                'ordering': ['id'],
            },
        ),
        migrations.AddField(
            model_name='detformula',
            name='unidad',
            field=models.CharField(default='g', max_length=3, verbose_name='Unidad Compra'),
            preserve_default=False,
        ),
    ]
