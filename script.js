new Vue({
  el: '#app',
  data() {
    return {
      newPupuk: {
        nama: '',
        harga: 0,
        stok: 0
      },
      pupukList: JSON.parse(localStorage.getItem('pupukList')) || [],
      penjualanList: JSON.parse(localStorage.getItem('penjualanList')) || [],
      selectedPupukIndex: '',
      jumlah: 1,
      namaSupir: '',
      tanggalPengambilan: ''
    };
  },
  computed: {
    totalPenjualan() {
      return this.penjualanList.reduce((sum, item) => sum + item.total, 0);
    },
    totalBarang() {
      return this.pupukList.reduce((sum, pupuk) => sum + (pupuk.harga * pupuk.stok), 0);
    }
  },
  methods: {
    tambahPupuk() {
      if (this.newPupuk.nama && this.newPupuk.harga > 0 && this.newPupuk.stok > 0) {
        this.pupukList.push({ ...this.newPupuk });
        this.saveData(); // Simpan data setelah menambah pupuk
        this.clearNewPupuk();
        this.$forceUpdate(); // Memaksa pembaruan tampilan
        Swal.fire({
          icon: 'success',
          title: 'Pupuk berhasil ditambahkan!',
          showConfirmButton: false,
          timer: 1500
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Lengkapi data pupuk!',
          text: 'Nama, harga, dan stok harus diisi dengan benar.'
        });
      }
    },
    restok(index) {
      Swal.fire({
        title: 'Masukkan jumlah restok:',
        input: 'number',
        inputAttributes: {
          min: 1
        },
        showCancelButton: true,
        confirmButtonText: 'Restok',
        cancelButtonText: 'Batal',
      }).then((result) => {
        if (result.isConfirmed) {
          const jumlahRestok = parseInt(result.value);
          if (jumlahRestok && !isNaN(jumlahRestok)) {
            // Menambahkan jumlah restok ke stok yang ada
            this.pupukList[index].stok += jumlahRestok;
            this.saveData(); // Simpan data setelah restok
            this.$forceUpdate(); // Memaksa pembaruan tampilan
            Swal.fire({
              icon: 'success',
              title: 'Stok berhasil diperbarui!',
              showConfirmButton: false,
              timer: 1500
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Jumlah tidak valid!',
            });
          }
        }
      });
    },
    hapusBarang(index) {
      Swal.fire({
        title: 'Apakah Anda yakin ingin menghapus barang ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
      }).then((result) => {
        if (result.isConfirmed) {
          this.pupukList.splice(index, 1);
          this.saveData();
          this.$forceUpdate(); // Memaksa pembaruan tampilan
          Swal.fire('Dihapus!', 'Barang berhasil dihapus.', 'success');
        }
      });
    },
    prosesPenjualan() {
      if (this.selectedPupukIndex === '' || this.jumlah <= 0 || !this.namaSupir || !this.tanggalPengambilan) {
        Swal.fire({
          icon: 'error',
          title: 'Lengkapi semua data!',
          text: 'Pastikan pupuk, jumlah, supir, dan tanggal diisi.'
        });
      } else {
        const pupuk = this.pupukList[this.selectedPupukIndex];
        if (pupuk.stok >= this.jumlah) {
          const total = pupuk.harga * this.jumlah;
          this.penjualanList.push({
            nama: pupuk.nama,
            harga: pupuk.harga,
            jumlah: this.jumlah,
            total,
            supir: this.namaSupir,
            tanggal: this.tanggalPengambilan
          });
          pupuk.stok -= this.jumlah;
          this.saveData();
          this.clearKasirForm();
          this.$forceUpdate(); // Memaksa pembaruan tampilan
          Swal.fire({
            icon: 'success',
            title: 'Penjualan berhasil!',
            showConfirmButton: false,
            timer: 1500
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Stok tidak cukup!',
            text: 'Jumlah yang diminta melebihi stok yang tersedia.'
          });
        }
      }
    },
    editPenjualan(index) {
      const penjualan = this.penjualanList[index];
      Swal.fire({
        title: 'Edit Penjualan',
        html: `
          <input id="jumlah" class="swal2-input" value="${penjualan.jumlah}" type="number" min="1">
          <input id="supir" class="swal2-input" value="${penjualan.supir}" placeholder="Nama Supir">
          <input id="tanggal" class="swal2-input" value="${penjualan.tanggal}" type="date">
        `,
        showCancelButton: true,
        confirmButtonText: 'Simpan',
        cancelButtonText: 'Batal',
      }).then((result) => {
        if (result.isConfirmed) {
          const jumlah = parseInt(document.getElementById('jumlah').value);
          const supir = document.getElementById('supir').value;
          const tanggal = document.getElementById('tanggal').value;

          if (jumlah && supir && tanggal) {
            penjualan.jumlah = jumlah;
            penjualan.supir = supir;
            penjualan.tanggal = tanggal;
            penjualan.total = penjualan.harga * jumlah;
            this.saveData();
            this.$forceUpdate(); // Memaksa pembaruan tampilan
            Swal.fire('Diperbarui!', 'Penjualan berhasil diperbarui.', 'success');
          } else {
            Swal.fire('Error', 'Semua field harus diisi', 'error');
          }
        }
      });
    },
    hapusPenjualan(index) {
      Swal.fire({
        title: 'Apakah Anda yakin ingin menghapus penjualan ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
      }).then((result) => {
        if (result.isConfirmed) {
          this.penjualanList.splice(index, 1);
          this.saveData();
          this.$forceUpdate(); // Memaksa pembaruan tampilan
          Swal.fire('Dihapus!', 'Penjualan berhasil dihapus.', 'success');
        }
      });
    },
    editPupuk(index) {
      const pupuk = this.pupukList[index];
      Swal.fire({
        title: 'Edit Pupuk',
        html: `
          <input id="nama" class="swal2-input" value="${pupuk.nama}" placeholder="Nama Pupuk">
          <input id="harga" class="swal2-input" value="${pupuk.harga}" type="number" placeholder="Harga">
          <input id="stok" class="swal2-input" value="${pupuk.stok}" type="number" placeholder="Stok">
        `,
        showCancelButton: true,
        confirmButtonText: 'Simpan',
        cancelButtonText: 'Batal',
      }).then((result) => {
        if (result.isConfirmed) {
          const nama = document.getElementById('nama').value;
          const harga = parseFloat(document.getElementById('harga').value);
          const stok = parseInt(document.getElementById('stok').value);

          if (nama && harga > 0 && stok >= 0) {
            this.pupukList[index] = { nama, harga, stok };
            this.saveData();
            this.$forceUpdate(); // Memaksa pembaruan tampilan
            Swal.fire('Diperbarui!', 'Pupuk berhasil diperbarui.', 'success');
          } else {
            Swal.fire('Error', 'Semua field harus diisi dengan benar', 'error');
          }
        }
      });
    },
    saveData() {
      localStorage.setItem('pupukList', JSON.stringify(this.pupukList));
      localStorage.setItem('penjualanList', JSON.stringify(this.penjualanList));
    },
    clearNewPupuk() {
      this.newPupuk.nama = '';
      this.newPupuk.harga = 0;
      this.newPupuk.stok = 0;
    },
    clearKasirForm() {
      this.selectedPupukIndex = '';
      this.jumlah = 1;
      this.namaSupir = '';
      this.tanggalPengambilan = '';
    },
    exportToExcel() {
      const tableHtml = `
        <h2>Tabel Barang</h2>
        ${this.createTableHtml(this.pupukList, ['Nama Pupuk', 'Harga', 'Stok', 'Total Harga Stok'])}
        <h2>Tabel Penjualan</h2>
        ${this.createTableHtml(this.penjualanList, ['Nama Pupuk', 'Harga', 'Jumlah', 'Total', 'Supir', 'Tanggal'])}
      `;
      const link = document.createElement('a');
      link.href = 'data:application/vnd.ms-excel,' + encodeURIComponent(tableHtml);
      link.download = 'manajemen_pupuk.xls';
      link.click();
    },
    createTableHtml(data, headers) {
      let html = '<table border="1"><tr>';
      headers.forEach(header => {
        html += `<th>${header}</th>`;
      });
      html += '</tr>';
      data.forEach(item => {
        html += '<tr>';
        Object.values(item).forEach((value, index) => {
          // Jika ini adalah tabel barang, tambahkan total harga stok
          if (headers[0] === 'Nama Pupuk' && index === 2) {
            html += `<td>${item.harga * item.stok}</td>`; // Total Harga Stok
          } else {
            html += `<td>${value}</td>`;
          }
        });
        html += '</tr>';
      });
      html += '</table>';
      return html;
    }
  },
  mounted() {
    this.saveData();
  }
});